<?php
defined('EMONCMS_EXEC') or die('Restricted access');
load_js("Lib/js/vue.global.prod-3.5.22.min.js");

$_js_translations = array(
	'System Information' => tr('System Information'),
	'Client Information' => tr('Client Information'),
	'Services' => tr('Services'),
	'Refresh' => tr('Refresh'),
	'Loading...' => tr('Loading...'),
	'Refresh failed' => tr('Refresh failed'),
	'Copy as Markdown' => tr('Copy as Markdown'),
	'Copy as Text' => tr('Copy as Text'),
	'**Recommended** when pasting into forum' => tr('**Recommended** when pasting into forum'),
	'Formatted as plain text' => tr('Formatted as plain text'),
	'Server info copied to clipboard as Markdown [text/markdown]' => tr('Server info copied to clipboard as Markdown [text/markdown]'),
	'Server info copied to clipboard as Text [text/plain]' => tr('Server info copied to clipboard as Text [text/plain]'),
	'Copied to clipboard' => tr('Copied to clipboard'),
	'Copy to clipboard: Ctrl+C, Enter' => tr('Copy to clipboard: Ctrl+C, Enter'),
	'Emoncms' => tr('Emoncms'),
	'Version' => tr('Version'),
	'Git' => tr('Git'),
	'URL' => tr('URL'),
	'Branch' => tr('Branch'),
	'Describe' => tr('Describe'),
	'Components' => tr('Components'),
	'Server' => tr('Server'),
	'Machine' => tr('Machine'),
	'CPU' => tr('CPU'),
	'OS' => tr('OS'),
	'Host' => tr('Host'),
	'Date' => tr('Date'),
	'Uptime' => tr('Uptime'),
	'Memory' => tr('Memory'),
	'RAM' => tr('RAM'),
	'Swap' => tr('Swap'),
	'Disk' => tr('Disk'),
	'HTTP' => tr('HTTP'),
	'MySQL' => tr('MySQL'),
	'Stats' => tr('Stats'),
	'Redis' => tr('Redis'),
	'Redis Server' => tr('Redis Server'),
	'Python Redis' => tr('Python Redis'),
	'PHP Redis' => tr('PHP Redis'),
	'MQTT Server' => tr('MQTT Server'),
	'PHP' => tr('PHP'),
	'Run user' => tr('Run user'),
	'Modules' => tr('Modules'),
	'Pi' => tr('Pi'),
	'Model' => tr('Model'),
	'Serial num.' => tr('Serial num.'),
	'CPU Temperature' => tr('CPU Temperature'),
	'GPU Temperature' => tr('GPU Temperature'),
	'emonpiRelease' => tr('emonpiRelease'),
	'File-system' => tr('File-system'),
	'Browser' => tr('Browser'),
	'Language' => tr('Language'),
	'Window' => tr('Window'),
	'Screen' => tr('Screen'),
	'Resolution' => tr('Resolution'),
	'Used: %s%%' => tr('Used: %s%%'),
	'%s days' => tr('%s days'),
	'Mosquitto %s' => tr('Mosquitto %s'),
	'keys' => tr('keys'),
	'Flush' => tr('Flush'),
	'Reset Disk Stats' => tr('Reset Disk Stats'),
	'Read Load' => tr('Read Load'),
	'Write Load' => tr('Write Load'),
	'Load Time' => tr('Load Time'),
	'Total' => tr('Total'),
	'Used' => tr('Used'),
	'Free' => tr('Free'),
	'User' => tr('User'),
	'Group' => tr('Group'),
	'Script Owner' => tr('Script Owner'),
	'Zend Version' => tr('Zend Version'),
	'Shutdown' => tr('Shutdown'),
	'Reboot' => tr('Reboot'),
	'feed points pending write' => tr('feed points pending write'),
	'Please confirm you wish to shutdown your Pi, please wait 30 secs before disconnecting the power...' => tr('Please confirm you wish to shutdown your Pi, please wait 30 secs before disconnecting the power...'),
	'Please confirm you wish to reboot your Pi, this will take approximately 30 secs to complete...' => tr('Please confirm you wish to reboot your Pi, this will take approximately 30 secs to complete...')
);
?>

<?php load_css("Modules/admin/static/admin_styles.css"); ?>

<div id="new-system-info" class="panel-page admin-page info-page" v-cloak>
	<div class="page-header">
		<h3>{{ tr('System Information') }}</h3>
		<div class="page-actions">
			<button type="button" class="btn btn-default" @click="refresh" :disabled="loading" :title="tr('Refresh')">
				<span v-if="loading">{{ tr('Loading...') }}</span>
				<span v-else><span class="svg-icon-refresh-cw"></span> {{ tr('Refresh') }}</span>
			</button>
			<button type="button" class="btn btn-info" @click="copyAsMarkdown" :title="tr('**Recommended** when pasting into forum')">{{ tr('Copy as Markdown') }}</button>
			<button type="button" class="btn btn-info" @click="copyAsText" :title="tr('Formatted as plain text')">{{ tr('Copy as Text') }}</button>
		</div>
	</div>

	<?php if (PHP_VERSION_ID < 70300) { ?>
	<div class="alert alert-danger">
		<b>Important:</b> PHP version <?php echo PHP_VERSION; ?> detected. Please update to version 7.3 or newer to keep your installation secure.<br>
		This emoncms installation is running in compatibility mode and does not include all of the latest security improvements.<br>
		See guide on updating php on the emoncms github: <a href="https://github.com/emoncms/emoncms/issues/1726">Updating PHP.</a>
	</div>
	<?php } ?>

	<div v-if="!hasLoaded" class="system-info-loading">
		<div class="system-info-spinner"></div>
		<div>{{ tr('Loading...') }}</div>
	</div>

	<template v-if="hasLoaded">
	<div class="panel">
		<div class="panel-header panel-header-static">
			<span class="panel-accent"></span>
			<span class="panel-name">{{ tr('Services') }}</span>
		</div>
		<div v-for="(svc, key) in info.Services" :key="key" class="panel-row info-row" @click="copyServiceRow(key, svc, $event)">
			<div class="row-key"><span class="status-dot" :class="serviceDotClass(svc)"></span>{{ key }}</div>
			<div class="row-value">
				<span class="badge px-2" :class="tagClass(serviceTag(svc).colour)">{{ serviceTag(svc).text }}</span>
				<span v-if="svc && svc.note" class="info-note">{{ svc.note }}</span>
			</div>
			<div class="btn-group svc-buttons" role="group" v-if="isServiceLoaded(svc) && svc.unitfilestate !== 'container'">
				<button v-if="svc.unitfilestate !== 'disabled' && !isServiceActive(svc)" class="btn btn-xs btn-success" @click="serviceAction(key, 'start')">Start</button>
				<button v-if="isServiceActive(svc)" class="btn btn-xs btn-danger" @click="serviceAction(key, 'stop')">Stop</button>
				<button v-if="isServiceActive(svc)" class="btn btn-xs btn-warning" @click="serviceAction(key, 'restart')">Restart</button>
				<button v-if="svc.unitfilestate === 'disabled'" class="btn btn-xs btn-primary" @click="serviceAction(key, 'enable')">Enable</button>
				<button v-else-if="!isServiceActive(svc)" class="btn btn-xs btn-dark" @click="serviceAction(key, 'disable')">Disable</button>
			</div>
		</div>
	</div>

	<div class="panel" v-for="section in serverSections" :key="section.title">
		<div class="panel-header panel-header-static">
			<span class="panel-accent"></span>
			<span class="panel-name">{{ tr(section.title) }}</span>
			<button v-if="section.title === 'Disk'" class="btn btn-info btn-sm" @click="resetDiskStats">{{ tr('Reset Disk Stats') }}</button>
		</div>
		<div v-for="row in section.rows" :key="row.title" class="panel-row info-row" @click="copyRow(row, $event)">
			<div class="row-key text-truncate" :title="tr(row.title)">{{ tr(row.title) }}</div>
			<div class="row-value">
				<template v-if="row.type === 'text'">{{ row.value }}</template>
				<span v-if="row.type === 'branch'" class="badge px-2" :class="tagClass(branchColour(row.value))">{{ row.value }}</span>
				<div v-if="row.type === 'components'" class="info-chips">
					<span v-for="c in row.items" :key="c.name" class="info-chip" :title="c.lc ? '<?php echo tr('Local changes'); ?>' : ''">{{ c.name }}<span class="info-chip-version">{{ c.version }}</span><span v-if="c.lc" class="badge bg-danger-subtle text-danger-emphasis">LC</span></span>
				</div>
				<div v-if="row.type === 'progress'" class="info-usage">
					<div class="info-usage-label">{{ tr(row.label) }}</div>
					<div class="progress"><div class="progress-bar" :class="usageClass(row.width)" :style="{ width: row.width + '%' }"></div></div>
					<div class="info-summary">
						<span v-for="item in row.summary" :key="item.k"><b>{{ item.k }}</b>{{ item.v }}</span>
					</div>
				</div>
				<ul v-if="row.type === 'list'" class="info-list" :class="{ 'list-columns': row.columns }"><li v-for="item in row.items" :key="item">{{ item }}</li></ul>
				<span v-if="row.type === 'redis-size'" id="redisused">{{ row.value }}</span>
			</div>
			<div class="row-buttons" v-if="row.type === 'redis-size'">
				<button id="redisflush" class="btn btn-info btn-sm" @click="redisFlush">{{ tr('Flush') }}</button>
			</div>
		</div>
	</div>

	<div class="panel" v-for="section in clientSections" :key="section.title || 'client'">
		<div class="panel-header panel-header-static">
			<span class="panel-accent"></span>
			<span class="panel-name">{{ tr('Client Information') }}</span>
		</div>
		<div v-for="row in section.rows" :key="row.title" class="panel-row info-row" @click="copyRow(row, $event)">
			<div class="row-key text-truncate">{{ tr(row.title) }}</div>
			<div class="row-value">{{ row.value }}</div>
		</div>
	</div>
	</template>

	<div class="panel">
		<div class="panel-header panel-header-static">
			<span class="panel-accent panel-accent-danger"></span>
			<span class="panel-name">{{ tr('Pi Control') }}</span>
		</div>
		<div class="panel-row">
			<div class="row-key">{{ tr('Reboot') }}</div>
			<div class="row-value text-muted">Takes about 30 seconds.</div>
			<button type="button" class="btn btn-warning" @click="rebootPi" :disabled="loading">{{ tr('Reboot') }}</button>
		</div>
		<div class="panel-row">
			<div class="row-key">{{ tr('Shutdown') }}</div>
			<div class="row-value text-muted">Wait 30 seconds before disconnecting the power.</div>
			<button type="button" class="btn btn-danger" @click="haltPi" :disabled="loading">{{ tr('Shutdown') }}</button>
		</div>
	</div>
</div>

<div id="snackbar" class=""></div>

<script>
var strings = <?php echo json_encode($_js_translations); ?>;
var adminPath = <?php echo json_encode($path . 'admin/'); ?>;

function tr(text) {
	return strings.hasOwnProperty(text) ? strings[text] : text;
}

function sprintf(fmt) {
	var args = Array.prototype.slice.call(arguments, 1);
	var i = 0;
	return fmt.replace(/%s|%%/g, function(m) {
		if (m === '%%') return '%';
		return typeof args[i] !== 'undefined' ? args[i++] : '';
	});
}

function snackbar(text) {
	var el = document.getElementById('snackbar');
	el.innerHTML = text;
	el.className = 'show';
	setTimeout(function() {
		el.className = el.className.replace('show', '');
	}, 3000); // SNACKBAR_TIMEOUT
}

function legacyCopyTextToClipboard(text, message) {

	var textArea = document.createElement('textarea');
	textArea.style.position = 'fixed';
	textArea.style.top = '0';
	textArea.style.left = '0';
	textArea.style.width = '2em';
	textArea.style.height = '2em';
	textArea.style.border = 'none';
	textArea.style.background = 'transparent';
	textArea.value = text;
	document.body.appendChild(textArea);
	textArea.select();
	try {
		var copied = document.execCommand('copy');
		if (copied) {
			snackbar(message || tr('Copied to clipboard'));
		} else {
			window.prompt(tr('Copy to clipboard: Ctrl+C, Enter'), text);
		}
	} catch (err) {
		window.prompt(tr('Copy to clipboard: Ctrl+C, Enter'), text);
	}
	document.body.removeChild(textArea);
}

function copyTextToClipboard(text, message) {
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(text)
			.then(function() {
				snackbar(message || tr('Copied to clipboard'));
			})
			.catch(function() {
				legacyCopyTextToClipboard(text, message);
			});
		return;
	}

	legacyCopyTextToClipboard(text, message);
}

Vue.createApp({
	data() {
		return {
			info: { Services: {}, 'System Information': {}, 'Client Information': {} },
			loading: false,
			hasLoaded: false,
			serviceActionInProgress: false,
			shuttingDown: false,
			SNACKBAR_TIMEOUT: 3000
		};
	},
	mounted: function() {
		// Keys with spaces are not reliably available via PHP extract() in views,
		// so load the canonical JSON payload once the page mounts.
		this.refresh(true);
		// next tick refresh not from cache
		this.$nextTick(function() {
			this.refresh(false);
		});
		
	},
	computed: {
		serverSections: function() {
			var source = this.info['System Information'] || {};
			var sections = [];
			for (var sectionTitle in source) {
				if (!source.hasOwnProperty(sectionTitle)) continue;
				sections.push({ title: sectionTitle, rows: this.toRows(source[sectionTitle], sectionTitle) });
			}
			return sections;
		},
		clientSections: function() {
			return [{ title: '', rows: this.toRows(this.info['Client Information'] || {}, 'Client Information') }];
		}
	},
	methods: {
		tr: tr,
		isServiceLoaded: function(svc) {
			return (svc && svc.loadstate === 'Loaded');
		},
		isServiceActive: function(svc) {
			return (svc && svc.state === 'Active');
		},
		isServiceRunning: function(svc) {
			return this.isServiceLoaded(svc) && this.isServiceActive(svc) && (svc.substate === 'Running');
		},
		serviceDotClass: function(svc) {
			if (!svc) return '';
			if (svc.loadstate === 'Not-found' || svc.loadstate === 'Masked') return '';
			return this.isServiceRunning(svc) ? 'is-running' : 'is-stopped';
		},
		// State tag: green running, amber active but not running, red stopped, grey not installed
		serviceTag: function(svc) {
			if (!svc || svc.loadstate === 'Not-found') return { text: 'Not installed', colour: 'secondary' };
			if (svc.loadstate === 'Masked') return { text: 'Masked', colour: 'secondary' };
			if (this.isServiceRunning(svc)) return { text: 'Running', colour: 'success' };
			if (this.isServiceActive(svc)) return { text: svc.substate || 'Active', colour: 'warning' };
			return { text: [svc.state, svc.substate].filter(Boolean).join(' ') || 'Stopped', colour: 'danger' };
		},
		branchColour: function(branch) {
			return (branch === 'master' || branch === 'stable') ? 'success' : 'warning';
		},
		// Pastel tag classes for a Bootstrap colour name
		tagClass: function(colour) {
			return 'bg-' + colour + '-subtle text-' + colour + '-emphasis';
		},
		usageClass: function(percent) {
			if (percent >= 90) return 'bg-danger';
			if (percent >= 70) return 'bg-warning';
			return 'bg-success';
		},
		// "Emoncms Core v11.19.2 [LC] | App v3.5.0" to name, version and local changes
		parseComponents: function(text) {
			return text.split('|').map(function(part) {
				var p = part.trim();
				var lc = /\[LC\]/.test(p);
				p = p.replace(/\s*\[LC\]/, '');
				var m = p.match(/^(.*?)\s+(v\S+)$/);
				return { name: m ? m[1] : p, version: m ? m[2] : '', lc: lc };
			}).filter(function(c) { return c.name; });
		},
		serviceText: function(svc) {
			if (!svc) return '';
			if (svc.loadstate === 'Not-found' || svc.loadstate === 'Masked') {
				return 'Not found or not installed';
			}
			var parts = [];
			if (svc.substate) {
				parts.push(svc.substate);
			}
			if (svc.note) {
				parts.push('- ' + svc.note);
			}
			if (parts.length > 0) {
				return parts.join(' ');
			}
			return [svc.loadstate || '', svc.state || ''].join(' ').trim();
		},
		toRows: function(sectionData, sectionTitle) {
			var rows = [];
			var isRedisSection = (sectionTitle || '').toLowerCase() === 'redis';
			if (!sectionData || typeof sectionData !== 'object' || Array.isArray(sectionData)) {
				return rows;
			}
			for (var key in sectionData) {
				if (!sectionData.hasOwnProperty(key)) continue;
				var value = sectionData[key];

				// Emoncms git branch and component list
				if (sectionTitle === 'Emoncms' && key === 'Git Branch' && typeof value === 'string' && value) {
					rows.push({ type: 'branch', title: key, value: value });
					continue;
				}
				if (sectionTitle === 'Emoncms' && key === 'Components' && typeof value === 'string' && value) {
					rows.push({ type: 'components', title: key, value: value, items: this.parseComponents(value) });
					continue;
				}

				// Redis size/keys row
				if (isRedisSection && (key === 'Size' || key === 'keys') && typeof value === 'string') {
					rows.push({ type: 'redis-size', title: key, value: value });
					continue;
				}

				// Progress row (object with a %-based Used field)
				if (value && typeof value === 'object' && !Array.isArray(value) && typeof value['Used'] === 'string' && value['Used'].indexOf('%') !== -1) {
					var usedPercent = value['Used'];
					var width = parseFloat(usedPercent.replace('%', ''));
					if (isNaN(width)) width = 0;
					var summary = [
						{ k: tr('Total'), v: value['Total'] || '' },
						{ k: tr('Used'),  v: value['Used Value'] || '' },
						{ k: tr('Free'),  v: value['Free'] || '' }
					];
					if (value['Read Load'])  summary.push({ k: tr('Read Load'),  v: value['Read Load'] });
					if (value['Write Load']) summary.push({ k: tr('Write Load'), v: value['Write Load'] });
					if (value['Load Time'])  summary.push({ k: tr('Load Time'),  v: value['Load Time'] });
					rows.push({
						type: 'progress',
						title: key,
						label: sprintf(tr('Used: %s%%'), usedPercent.replace('%', '')),
						width: width,
						summary: summary
					});
					continue;
				}

				// List row (array of strings)
				if (Array.isArray(value)) {
					rows.push({ type: 'list', title: key, items: value, columns: value.length > 8 });

				// Object row (plain object): render as "k: v | k: v" text
				} else if (value && typeof value === 'object') {
					var parts = [];
					for (var prop in value) {
						if (value.hasOwnProperty(prop)) parts.push(prop + ': ' + value[prop]);
					}
					rows.push({ type: 'text', title: key, value: parts.join(' | ') });

				// Text row (string or other primitive)
				} else {
					rows.push({ type: 'text', title: key, value: value || '' });
				}
			}
			return rows;
		},
		copyRow: function(row, evt) {
			if (evt && evt.target && evt.target.tagName === 'BUTTON') {
				return;
			}
			if (!row) {
				return;
			}
			var title = row.title || '';
			var value = this.rowToText(row);
			if (!title || !value) {
				return;
			}
			copyTextToClipboard(title + ': ' + value, tr('Copied to clipboard'));
		},
		copyServiceRow: function(name, svc, evt) {
			if (evt && evt.target && evt.target.tagName === 'BUTTON') {
				return;
			}
			if (!svc) {
				return;
			}
			var value = this.isServiceLoaded(svc) ? ((svc.state || '') + ' ' + this.serviceText(svc)).trim() : this.serviceText(svc);
			if (!value) {
				return;
			}
			copyTextToClipboard(name + ': ' + value, tr('Copied to clipboard'));
		},
		refresh: function(from_cache = false) {
			var self = this;
			self.loading = true;

			var action = "systeminfo";
			if (from_cache) {
				action = "systeminfocached";
			}

			fetch(adminPath + action, { credentials: 'same-origin' })
				.then(function(res) {
					if (!res.ok) throw new Error('http');
					return res.json();
				})
				.then(function(data) {
					if (data.reauth) {
						window.location.reload(true);
						return;
					}
					self.info = data;
					self.loading = false;
					self.hasLoaded = true;
				})
				.catch(function() {
					self.loading = false;
					// Stay silent once a shutdown/reboot has been initiated,
					// as the Pi becoming unreachable is expected.
					if (!self.shuttingDown) {
						snackbar(tr('Refresh failed'));
					}
				});
		},
		serviceAction: function(name, action) {
			var self = this;
			if (self.serviceActionInProgress) {
				return;
			}
			self.serviceActionInProgress = true;
			fetch(adminPath + 'service/' + action + '?name=' + encodeURIComponent(name), { credentials: 'same-origin' })
				.then(function(res) {
					if (!res.ok) throw new Error('http');
					return res.json();
				})
				.then(function(result) {
					self.serviceActionInProgress = false;
					if (result.reauth) {
						window.location.reload(true);
						return;
					}
					setTimeout(function() {
						window.location.reload();
					}, 1000);
				})
				.catch(function(err) {
					self.serviceActionInProgress = false;
					snackbar(tr('Refresh failed'));
				});
		},
		rowToText: function(row) {
			if (row.type === 'text' || row.type === 'branch' || row.type === 'components' || !row.type) return row.value || '';
			if (row.type === 'list') return (row.items || []).join(', ');
			if (row.type === 'progress') {
				var pairs = (row.summary || []).map(function(s) { return s.k + ': ' + s.v; });
				return row.label + ' | ' + pairs.join(' | ');
			}
			return '';
		},
		buildClipboardText: function(sections, format) {
			// format: 'markdown' or 'plain'
			var out = [];
			for (var i = 0; i < sections.length; i++) {
				var title = sections[i].title;
				if (title) {
					out.push(format === 'markdown' ? '## ' + title : '\n' + title + '\n-----------------------');
				}
				var rows = sections[i].rows;
				for (var r = 0; r < rows.length; r++) {
					var value = this.rowToText(rows[r]);
					if (value === '') continue;
					out.push(format === 'markdown'
						? ' - **' + rows[r].title + '**: ' + value
						: '\t' + rows[r].title + ':\t' + value);
				}
				out.push('');
			}
			return out.join('\n').replace(/\n{3,}/g, '\n\n').trim();
		},
		copyAsMarkdown: function() {
			var server = this.buildClipboardText(this.serverSections, 'markdown');
			var client = this.buildClipboardText(this.clientSections, 'markdown');
			var md = '<details><summary>' + tr('System Information') + '</summary>\n\n' +
				'# ' + tr('System Information') + '\n' + server + '\n</details>\n\n' +
				'<details><summary>' + tr('Client Information') + '</summary>\n\n' +
				'# ' + tr('Client Information') + '\n' + client + '\n</details>';
			copyTextToClipboard(md, tr('Server info copied to clipboard as Markdown [text/markdown]'));
		},
		copyAsText: function() {
			var server = this.buildClipboardText(this.serverSections, 'plain');
			var client = this.buildClipboardText(this.clientSections, 'plain');
			var txt = tr('System Information') + '\n-----------------------\n' + server + '\n\n' +
				tr('Client Information') + '\n-----------------------\n' + client;
			copyTextToClipboard(txt, tr('Server info copied to clipboard as Text [text/plain]'));
		},
		redisFlush: function() {
			if (!confirm('Are you sure you want to flush all Redis data?')) {
				return;
			}
			var self = this;
			fetch(adminPath + 'redis-flush', { credentials: 'same-origin' })
				.then(function(res) {
					if (!res.ok) throw new Error('http');
					return res.json();
				})
				.then(function(result) {
					if (result.reauth) {
						window.location.reload(true);
						return;
					}
					self.refresh();
				})
				.catch(function() {
					snackbar(tr('Refresh failed'));
				});
		},
		resetDiskStats: function() {
			if (!confirm('Are you sure you want to reset disk stats?')) {
				return;
			}
			var self = this;
			fetch(adminPath + 'reset-disk-stats', { credentials: 'same-origin' })
				.then(function(res) {
					if (!res.ok) throw new Error('http');
					return res.json();
				})
				.then(function(result) {
					if (result.reauth) {
						window.location.reload(true);
						return;
					}
					self.refresh();
				})
				.catch(function() {
					snackbar(tr('Refresh failed'));
				});
		},
		haltPi: function() {
			if (!confirm(tr('Please confirm you wish to shutdown your Pi, please wait 30 secs before disconnecting the power...'))) {
				return;
			}
			var self = this;
			self.loading = true;
			fetch(adminPath + 'shutdown', { credentials: 'same-origin' })
				.then(function(res) {
					if (!res.ok) throw new Error('http');
					return res.json();
				})
				.then(function(result) {
					if (result.reauth) {
						window.location.reload(true);
						return;
					}
					self.shuttingDown = true;
					snackbar('Pi shutting down...');
				})
				.catch(function() {
					self.loading = false;
					snackbar(tr('Refresh failed'));
				});
		},
		rebootPi: function() {
			if (!confirm(tr('Please confirm you wish to reboot your Pi, this will take approximately 30 secs to complete...'))) {
				return;
			}
			var self = this;
			self.loading = true;
			fetch(adminPath + 'reboot', { credentials: 'same-origin' })
				.then(function(res) {
					if (!res.ok) throw new Error('http');
					return res.json();
				})
				.then(function(result) {
					if (result.reauth) {
						window.location.reload(true);
						return;
					}
					self.shuttingDown = true;
					snackbar('Pi rebooting...');
				})
				.catch(function() {
					self.loading = false;
					snackbar(tr('Refresh failed'));
				});
		}
	}
}).mount('#new-system-info');
</script>
