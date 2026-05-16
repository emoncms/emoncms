const DateTimePicker = {
	name: 'DateTimePicker',

	template: `
		<div class="dtp-wrap" ref="wrapRef">
			<div class="dtp-input-row">
				<slot name="label" />
				<div class="dtp-input-wrap">
					<input
						class="dtp-input"
						type="text"
						v-model="localInput"
						@keydown.enter.prevent="commitInput"
						@blur="onInputBlur"
						:placeholder="placeholder"
					/>
					<div class="dtp-input-btns">
						<button class="dtp-icon-btn" @click.stop="toggle" tabindex="-1" aria-label="Open calendar">&#9662;</button>
					</div>
				</div>
			</div>

			<div v-if="open" class="dtp-popup">

				<div class="dtp-calendar-header">
					<button class="dtp-nav" @click="prevMonth">&#8249;</button>
					<span class="dtp-month-label">{{ monthLabel }}</span>
					<button class="dtp-nav" @click="nextMonth">&#8250;</button>
				</div>

				<div class="dtp-dow-row">
					<span v-for="d in dowLabels" :key="d" class="dtp-dow">{{ d }}</span>
				</div>

				<div class="dtp-days">
					<button
						v-for="cell in calendarCells"
						:key="cell.key"
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
					<div class="dtp-time-group">
						<button class="dtp-t-btn" @click="adjustTime('h', 1)">&#9650;</button>
						<input class="dtp-t-input" type="text" :value="pad(tempH)" @change="onHourInput" maxlength="2" />
						<button class="dtp-t-btn" @click="adjustTime('h', -1)">&#9660;</button>
					</div>
					<span class="dtp-colon">:</span>
					<div class="dtp-time-group">
						<button class="dtp-t-btn" @click="adjustTime('m', 1)">&#9650;</button>
						<input class="dtp-t-input" type="text" :value="pad(tempM)" @change="onMinInput" maxlength="2" />
						<button class="dtp-t-btn" @click="adjustTime('m', -1)">&#9660;</button>
					</div>
					<span class="dtp-colon">:</span>
					<div class="dtp-time-group">
						<button class="dtp-t-btn" @click="adjustTime('s', 1)">&#9650;</button>
						<input class="dtp-t-input" type="text" :value="pad(tempS)" @change="onSecInput" maxlength="2" />
						<button class="dtp-t-btn" @click="adjustTime('s', -1)">&#9660;</button>
					</div>
				</div>

				<div class="dtp-footer">
					<button class="dtp-btn-now" @click="setNow">Now</button>
					<button class="dtp-btn-apply" @click="apply">Apply</button>
				</div>

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
		}
	},

	emits: ['update:modelValue', 'change'],

	data() {
		const now = new Date()
		return {
			open: false,
			viewYear: now.getFullYear(),
			viewMonth: now.getMonth(),
			selectedDate: null,
			tempH: 0,
			tempM: 0,
			tempS: 0,
			dowLabels: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
			localInput: '',
		}
	},

	computed: {
		monthLabel() {
			return new Date(this.viewYear, this.viewMonth, 1)
				.toLocaleString('default', { month: 'long', year: 'numeric' })
		},

		calendarCells() {
			const year = this.viewYear
			const month = this.viewMonth
			const firstDay = new Date(year, month, 1).getDay()
			const daysInMonth = new Date(year, month + 1, 0).getDate()
			const daysInPrev = new Date(year, month, 0).getDate()
			const today = new Date()
			const todayStr = this.fmt(today.getFullYear(), today.getMonth() + 1, today.getDate(), 0, 0, 0).slice(0, 10)
			const cells = []

			for (let i = firstDay - 1; i >= 0; i--) {
				const d = daysInPrev - i
				cells.push({ key: `p${d}`, d, current: false, selected: false, today: false, year, month: month - 1 })
			}
			for (let d = 1; d <= daysInMonth; d++) {
				const dateStr = this.fmt(year, month + 1, d, 0, 0, 0).slice(0, 10)
				const selStr = this.selectedDate ? this.fmt(
					this.selectedDate.getFullYear(),
					this.selectedDate.getMonth() + 1,
					this.selectedDate.getDate(), 0, 0, 0
				).slice(0, 10) : null
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
				if (val) {
					const d = new Date(val.replace(' ', 'T'))
					if (!isNaN(d)) {
						this.selectedDate = d
						this.viewYear = d.getFullYear()
						this.viewMonth = d.getMonth()
						this.tempH = d.getHours()
						this.tempM = d.getMinutes()
						this.tempS = d.getSeconds()
						this.localInput = this.fmt(d.getFullYear(), d.getMonth() + 1, d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds())
					}
				} else {
					this.localInput = ''
				}
			}
		}
	},

	mounted() {
		document.addEventListener('click', this.onOutsideClick)
	},

	beforeUnmount() {
		document.removeEventListener('click', this.onOutsideClick)
	},

	methods: {
		toggle() {
			this.open = !this.open
		},

		onOutsideClick(e) {
			if (this.$refs.wrapRef && !this.$refs.wrapRef.contains(e.target)) {
				this.open = false
			}
		},

		onInputBlur(e) {
			// Don't commit if focus moved to another element inside this component
			if (this.$refs.wrapRef && this.$refs.wrapRef.contains(e.relatedTarget)) return
			this.commitInput()
		},

		commitInput() {
			const raw = (this.localInput || '').trim()
			if (!raw) {
				this.selectedDate = null
				this.$emit('update:modelValue', '')
				this.$emit('change', '')
				return
			}
			const d = new Date(raw.replace(' ', 'T'))
			if (isNaN(d)) {
				// Revert to last known good value
				this.localInput = this.modelValue
					? this.fmt(
						new Date(this.modelValue.replace(' ', 'T')).getFullYear(),
						new Date(this.modelValue.replace(' ', 'T')).getMonth() + 1,
						new Date(this.modelValue.replace(' ', 'T')).getDate(),
						new Date(this.modelValue.replace(' ', 'T')).getHours(),
						new Date(this.modelValue.replace(' ', 'T')).getMinutes(),
						new Date(this.modelValue.replace(' ', 'T')).getSeconds()
					  )
					: ''
				return
			}
			this.selectedDate = d
			this.viewYear = d.getFullYear()
			this.viewMonth = d.getMonth()
			this.tempH = d.getHours()
			this.tempM = d.getMinutes()
			this.tempS = d.getSeconds()
			const str = this.fmt(d.getFullYear(), d.getMonth() + 1, d.getDate(), d.getHours(), d.getMinutes(), d.getSeconds())
			this.localInput = str
			this.open = false
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
			const d = this.selectedDate ? new Date(this.selectedDate) : new Date()
			d.setFullYear(cell.year)
			d.setMonth(cell.month)
			d.setDate(cell.d)
			this.selectedDate = d
			if (!cell.current) {
				this.viewYear = cell.year
				this.viewMonth = ((cell.month % 12) + 12) % 12
			}
		},

		adjustTime(unit, delta) {
			if (unit === 'h') this.tempH = ((this.tempH + delta) + 24) % 24
			if (unit === 'm') this.tempM = ((this.tempM + delta) + 60) % 60
			if (unit === 's') this.tempS = ((this.tempS + delta) + 60) % 60
		},

		onHourInput(e) {
			const v = parseInt(e.target.value)
			if (!isNaN(v)) this.tempH = Math.min(23, Math.max(0, v))
		},
		onMinInput(e) {
			const v = parseInt(e.target.value)
			if (!isNaN(v)) this.tempM = Math.min(59, Math.max(0, v))
		},
		onSecInput(e) {
			const v = parseInt(e.target.value)
			if (!isNaN(v)) this.tempS = Math.min(59, Math.max(0, v))
		},

		setNow() {
			const now = new Date()
			this.selectedDate = now
			this.viewYear = now.getFullYear()
			this.viewMonth = now.getMonth()
			this.tempH = now.getHours()
			this.tempM = now.getMinutes()
			this.tempS = now.getSeconds()
		},

		apply() {
			if (!this.selectedDate) return
			const d = this.selectedDate
			const str = this.fmt(d.getFullYear(), d.getMonth() + 1, d.getDate(), this.tempH, this.tempM, this.tempS)
			this.localInput = str
			this.$emit('update:modelValue', str)
			this.$emit('change', str)
			this.open = false
		},

		fmt(Y, M, D, h, m, s) {
			return `${Y}-${this.pad(M)}-${this.pad(D)} ${this.pad(h)}:${this.pad(m)}:${this.pad(s)}`
		},

		pad(n) {
			return String(n).padStart(2, '0')
		}
	}
}
