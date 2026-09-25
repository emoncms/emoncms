// Date and time picker, Vue 3 component on Bootstrap 5 input group and dropdown.
//
// In a Vue template it renders an input, a calendar button and the dropdown menu,
// to sit inside an .input-group:
//
//     <div class="input-group">
//         <span class="input-group-text">Start</span>
//         <date-time-picker v-model="start" @change="reload"></date-time-picker>
//     </div>
//
// Without Vue templates, DateTimePicker.attach(input, options) adds the button and
// menu after an existing input, see the end of this file.
//
// Values are local time strings, YYYY-MM-DD HH:MM:SS.

const DateTimePicker = {
	name: 'DateTimePicker',

	template: `
		<input
			v-if="!input"
			ref="ownInput"
			class="form-control dtp-input"
			type="text"
			:placeholder="placeholder"
			@keydown.enter.prevent="commitInput"
			@blur="onInputBlur"
		/>
		<button
			ref="toggle"
			type="button"
			:class="buttonClass"
			class="dtp-toggle dropdown-toggle"
			data-bs-toggle="dropdown"
			data-bs-auto-close="outside"
			aria-expanded="false"
			@pointerdown="dropdownInstance"
			@keydown="dropdownInstance"
			:aria-label="t('Open calendar')"
			:title="t('Open calendar')"
		><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg></button>
		<div ref="menu" class="dropdown-menu dtp-popup">
			<div class="dtp-calendar-header">
				<button type="button" class="dtp-nav" @click="prevMonth" :aria-label="t('Previous month')">&#8249;</button>
				<span class="dtp-month-label">{{ monthLabel }}</span>
				<button type="button" class="dtp-nav" @click="nextMonth" :aria-label="t('Next month')">&#8250;</button>
			</div>

			<div class="dtp-dow-row">
				<span v-for="d in dowLabels" :key="d" class="dtp-dow">{{ d }}</span>
			</div>

			<div class="dtp-days">
				<button
					v-for="cell in calendarCells"
					:key="cell.key"
					type="button"
					class="dtp-day"
					:class="{
						'dtp-day--other': !cell.current,
						'dtp-day--selected': cell.selected,
						'dtp-day--today': cell.today,
					}"
					@click="selectDay(cell)"
				>{{ cell.d }}</button>
			</div>

			<div class="dtp-time-row">
				<template v-for="(unit, i) in ['h', 'm', 's']" :key="unit">
					<span v-if="i" class="dtp-colon">:</span>
					<div class="dtp-time-group">
						<button type="button" class="dtp-t-btn" @click="adjustTime(unit, 1)">&#9650;</button>
						<input class="form-control form-control-sm dtp-t-input" type="text" :value="pad(time[unit])" @change="onTimeInput(unit, $event)" maxlength="2" />
						<button type="button" class="dtp-t-btn" @click="adjustTime(unit, -1)">&#9660;</button>
					</div>
				</template>
			</div>

			<div class="dtp-footer">
				<button type="button" class="btn btn-sm btn-default" @click="setNow">{{ t('Now') }}</button>
				<button type="button" class="btn btn-sm btn-primary" @click="apply" :disabled="!selectedDate">{{ t('Apply') }}</button>
			</div>
		</div>
	`,

	props: {
		modelValue: {
			type: String,
			default: ''
		},
		placeholder: {
			type: String,
			default: 'YYYY-MM-DD HH:MM:SS'
		},
		// An existing input to use in place of the component's own
		input: {
			type: Object,
			default: null
		},
		buttonClass: {
			type: String,
			default: 'btn btn-default'
		}
	},

	emits: ['update:modelValue', 'change'],

	data() {
		const now = new Date()
		return {
			viewYear: now.getFullYear(),
			viewMonth: now.getMonth(),
			selectedDate: null,
			time: { h: 0, m: 0, s: 0 },
		}
	},

	computed: {
		monthLabel() {
			return new Date(this.viewYear, this.viewMonth, 1)
				.toLocaleString('default', { month: 'long', year: 'numeric' })
		},

		// Sunday first, 5 January 2025 was a Sunday
		dowLabels() {
			const labels = []
			for (let i = 0; i < 7; i++) {
				labels.push(new Date(2025, 0, 5 + i).toLocaleString('default', { weekday: 'short' }).slice(0, 2))
			}
			return labels
		},

		calendarCells() {
			const year = this.viewYear
			const month = this.viewMonth
			const firstDay = new Date(year, month, 1).getDay()
			const daysInMonth = new Date(year, month + 1, 0).getDate()
			const daysInPrev = new Date(year, month, 0).getDate()
			const todayStr = this.dayKey(new Date())
			const selStr = this.selectedDate ? this.dayKey(this.selectedDate) : null
			const cells = []

			for (let i = firstDay - 1; i >= 0; i--) {
				const d = daysInPrev - i
				cells.push({ key: `p${d}`, d, current: false, selected: false, today: false, year, month: month - 1 })
			}
			for (let d = 1; d <= daysInMonth; d++) {
				const dateStr = this.dayKey(new Date(year, month, d))
				cells.push({
					key: `c${d}`, d, current: true,
					selected: selStr === dateStr,
					today: todayStr === dateStr,
					year, month
				})
			}
			const remaining = 42 - cells.length
			for (let d = 1; d <= remaining; d++) {
				cells.push({ key: `n${d}`, d, current: false, selected: false, today: false, year, month: month + 1 })
			}
			return cells
		}
	},

	watch: {
		modelValue: {
			immediate: true,
			handler(val) {
				this.$nextTick(() => {
					const d = DateTimePicker.parse(val)
					if (d) this.show(d)
					this.inputEl().value = d ? DateTimePicker.format(d) : ''
				})
			}
		}
	},

	mounted() {
		if (window.bootstrap) this.dropdownInstance()
		if (this.input) {
			this.onKeydown = e => { if (e.key === 'Enter') { e.preventDefault(); this.commitInput() } }
			this.input.addEventListener('keydown', this.onKeydown)
			this.input.addEventListener('blur', this.onInputBlur)
		}
	},

	beforeUnmount() {
		if (this.dropdown) this.dropdown.dispose()
		if (this.input) {
			this.input.removeEventListener('keydown', this.onKeydown)
			this.input.removeEventListener('blur', this.onInputBlur)
		}
	},

	methods: {
		t(text) {
			return typeof _Tr === 'function' ? _Tr(text) : text
		},

		inputEl() {
			return this.input || this.$refs.ownInput
		},

		// Created on mount, or on the pointerdown or keydown before the first click
		// when a page mounts Vue before the Bootstrap bundle loads. Bootstrap's click
		// handler runs first, in the capture phase, and would create a default instance.
		dropdownInstance() {
			if (this.dropdown) return this.dropdown
			const toggle = this.$refs.toggle
			this.dropdown = bootstrap.Dropdown.getOrCreateInstance(toggle, {
				autoClose: 'outside',
				reference: this.inputEl(),
				// Fixed, so a scrolling modal body does not clip the menu
				popperConfig: { strategy: 'fixed', placement: 'bottom-start' }
			})
			// Opens on the date in the input
			toggle.addEventListener('show.bs.dropdown', () => {
				const d = DateTimePicker.parse(this.inputEl().value)
				if (d) this.show(d)
			})
			return this.dropdown
		},

		dayKey(d) {
			return DateTimePicker.format(d).slice(0, 10)
		},

		// Popup state from a date, without emitting
		show(d) {
			this.selectedDate = d
			this.viewYear = d.getFullYear()
			this.viewMonth = d.getMonth()
			this.time = { h: d.getHours(), m: d.getMinutes(), s: d.getSeconds() }
		},

		onInputBlur(e) {
			// Focus moved into the button or menu
			if (e.relatedTarget && (e.relatedTarget === this.$refs.toggle || this.$refs.menu.contains(e.relatedTarget))) return
			this.commitInput()
		},

		commitInput() {
			const el = this.inputEl()
			const raw = el.value.trim()
			if (!raw) {
				if (!this.modelValue) return
				this.selectedDate = null
				this.emit('')
				return
			}
			const d = DateTimePicker.parse(raw)
			if (!d) {
				// Back to the last good value
				const last = DateTimePicker.parse(this.modelValue)
				el.value = last ? DateTimePicker.format(last) : ''
				return
			}
			const str = DateTimePicker.format(d)
			el.value = str
			this.show(d)
			if (str !== this.modelValue) this.emit(str)
		},

		emit(str) {
			this.$emit('update:modelValue', str)
			this.$emit('change', str)
		},

		prevMonth() {
			if (this.viewMonth === 0) { this.viewMonth = 11; this.viewYear-- }
			else this.viewMonth--
		},

		nextMonth() {
			if (this.viewMonth === 11) { this.viewMonth = 0; this.viewYear++ }
			else this.viewMonth++
		},

		selectDay(cell) {
			this.selectedDate = new Date(cell.year, cell.month, cell.d)
			if (!cell.current) {
				this.viewYear = this.selectedDate.getFullYear()
				this.viewMonth = this.selectedDate.getMonth()
			}
		},

		adjustTime(unit, delta) {
			const max = unit === 'h' ? 24 : 60
			this.time[unit] = (this.time[unit] + delta + max) % max
		},

		onTimeInput(unit, e) {
			const v = parseInt(e.target.value, 10)
			if (!isNaN(v)) this.time[unit] = Math.min(unit === 'h' ? 23 : 59, Math.max(0, v))
			e.target.value = this.pad(this.time[unit])
		},

		setNow() {
			this.show(new Date())
		},

		apply() {
			if (!this.selectedDate) return
			const d = this.selectedDate
			const str = DateTimePicker.format(new Date(d.getFullYear(), d.getMonth(), d.getDate(), this.time.h, this.time.m, this.time.s))
			this.inputEl().value = str
			this.dropdownInstance().hide()
			this.emit(str)
		},

		pad(n) {
			return String(n).padStart(2, '0')
		}
	}
}

// Local time from YYYY-MM-DD, with optional HH:MM or HH:MM:SS. Null if invalid.
DateTimePicker.parse = function (str) {
	const m = /^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/.exec((str || '').trim())
	if (!m) return null
	const d = new Date(+m[1], m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0))
	// Rejects overflow such as 2025-02-30
	if (d.getMonth() !== m[2] - 1 || d.getDate() !== +m[3]) return null
	return d
}

DateTimePicker.format = function (d) {
	const p = n => String(n).padStart(2, '0')
	return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`
}

// Adds the picker to an existing input inside an .input-group, for pages without
// Vue templates. The input keeps its id, value and events.
//
// options.value       initial Date
// options.onChange    called with a Date, or null when cleared
// options.buttonClass button classes, default "btn btn-default"
//
// Returns { getDate(), setDate(date) }. setDate does not call onChange.
DateTimePicker.attach = function (input, options) {
	options = options || {}
	const host = document.createElement('span')
	host.className = 'dtp-host'
	input.after(host)

	const state = Vue.reactive({ value: options.value ? DateTimePicker.format(options.value) : '' })
	Vue.createApp({
		render() {
			return Vue.h(DateTimePicker, {
				input,
				modelValue: state.value,
				buttonClass: options.buttonClass || 'btn btn-default',
				'onUpdate:modelValue': v => { state.value = v },
				onChange: v => {
					// A change event for page code that listens on the input
					input.dispatchEvent(new Event('change', { bubbles: true }))
					if (options.onChange) options.onChange(DateTimePicker.parse(v))
				}
			})
		}
	}).mount(host)

	return {
		getDate() {
			return DateTimePicker.parse(input.value)
		},
		setDate(date) {
			state.value = date ? DateTimePicker.format(date) : ''
		}
	}
}
