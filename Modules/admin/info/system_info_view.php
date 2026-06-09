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

<link rel="stylesheet" href="<?php echo $path; ?>Modules/admin/static/admin_styles.css?v=3">

<div id="new-system-info" class="admin-container">
	<?php if (PHP_VERSION_ID < 70300) { ?>
	<div class="alert alert-error" style="text-align:left">
		<b>Important:</b> PHP version <?php echo PHP_VERSION; ?> detected. Please update to version 7.3 or newer to keep your installation secure.<br>
		This emoncms installation is running in compatibility mode and does not include all of the latest security improvements.<br>
		See guide on updating php on the emoncms github: <a href="https://github.com/emoncms/emoncms/issues/1726">Updating PHP.</a>
	</div>
	<?php } ?>

	<div class="d-md-flex justify-content-between align-items-center pb-md-2 pb-2 text-right px-1">
		<div class="text-left">
			<h3 class="mt-1 mb-0">{{ tr('System Information') }}</h3>
		</div>
		<div>
			<button type="button" class="btn btn-default mr-1" @click="refresh" :disabled="loading" :title="tr('Refresh')">
				<span v-if="loading">{{ tr('Loading...') }}</span>
				<span v-else>&#8635; {{ tr('Refresh') }}</span>
			</button>
			<button type="button" class="btn btn-info mr-1" @click="copyAsMarkdown" :title="tr('**Recommended** when pasting into forum')">{{ tr('Copy as Markdown') }}</button>
			<button type="button" class="btn btn-info" @click="copyAsText" :title="tr('Formatted as plain text')">{{ tr('Copy as Text') }}</button>
		</div>
	</div>

	<div v-if="!hasLoaded" class="system-info-loading">
		<div class="system-info-spinner"></div>
		<div>{{ tr('Loading...') }}</div>
	</div>

	<template v-if="hasLoaded">
	<h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1">{{ tr('Services') }}</h4>
	<dl class="row">
		<template v-for="(svc, key) in info.Services" :key="key">
			<dt class="col-sm-2 col-4 text-truncate" @click="copyServiceRow(key, svc, $event)"><span :class="'badge badge-' + serviceCssClass(svc)"></span> {{ key }}</dt>
			<dd class="col-sm-10 col-8 border-box px-1" @click="copyServiceRow(key, svc, $event)">
				<template v-if="isServiceLoaded(svc)">
					<strong>{{ svc.state }}</strong> {{ serviceText(svc) }}
					<div class="btn-group" role="group" style="float:right">
						<template v-if="svc.unitfilestate !== 'container'">
							<button v-if="svc.unitfilestate !== 'disabled' && !isServiceActive(svc)" class="btn btn-small btn-success" @click="serviceAction(key, 'start')">Start</button>
							<button v-if="isServiceActive(svc)" class="btn btn-small btn-danger" @click="serviceAction(key, 'stop')">Stop</button>
							<button v-if="isServiceActive(svc)" class="btn btn-small btn-warning" @click="serviceAction(key, 'restart')">Restart</button>
							<button v-if="svc.unitfilestate === 'disabled'" class="btn btn-small btn-primary" @click="serviceAction(key, 'enable')">Enable</button>
							<button v-else-if="!isServiceActive(svc)" class="btn btn-small btn-inverse" @click="serviceAction(key, 'disable')">Disable</button>
						</template>
					</div>
				</template>
				<template v-else>{{ serviceText(svc) }}</template>
			</dd>
		</template>
	</dl>

	<template v-for="section in serverSections" :key="section.title">
		<h4 class="text-info text-uppercase border-top pt-2 mt-0 px-1 d-flex justify-content-between align-items-center">
			<span>{{ tr(section.title) }}</span>
			<button v-if="section.title === 'Disk'" class="btn btn-info btn-small" @click="resetDiskStats">{{ tr('Reset Disk Stats') }}</button>
		</h4>
		<dl class="row">
			<template v-for="row in section.rows" :key="row.title">
				<dt :class="row.titleClass || 'col-sm-2 col-4 text-truncate'" @click="copyRow(row, $event)">{{ tr(row.title) }}</dt>
				<dd :class="row.valueClass || 'col-sm-10 col-8 border-box px-1'" @click="copyRow(row, $event)">
					<template v-if="row.type === 'text'">{{ row.value }}</template>
					<template v-if="row.type === 'progress'">
						<h5 class="m-0">{{ tr(row.label) }}</h5>
						<div class="progress progress-info mb-0"><div class="bar" :style="{ width: row.width + '%' }"></div></div>
						<dl class="inline">
							<template v-for="item in row.summary" :key="item.k">
								<dt class="pl-0">{{ item.k }}</dt>
								<dd>{{ item.v }}</dd>
							</template>
						</dl>
					</template>
					<template v-if="row.type === 'list'">
						<ul :id="row.listId || null" :class="{ 'list-columns': row.columns }"><li v-for="item in row.items" :key="item">{{ item }}</li></ul>
					</template>
					<template v-if="row.type === 'redis-size'">
						<span id="redisused">{{ row.value }}</span>
						<button id="redisflush" class="btn btn-info btn-small pull-right" @click="redisFlush">{{ tr('Flush') }}</button>
					</template>
				</dd>
			</template>
		</dl>
	</template>

	<h3 class="mt-1 mb-0">{{ tr('Client Information') }}</h3>
	<template v-for="section in clientSections" :key="section.title || 'client'">
		<h4 v-if="section.title" class="text-info text-uppercase border-top pt-2 mt-0 px-1">{{ tr(section.title) }}</h4>
		<dl class="row">
			<template v-for="row in section.rows" :key="row.title">
				<dt class="col-sm-2 col-4 text-truncate" @click="copyRow(row, $event)">{{ tr(row.title) }}</dt>
				<dd class="col-sm-10 col-8 border-box px-1" @click="copyRow(row, $event)">{{ row.value }}</dd>
			</template>
		</dl>
	</template>
	</template>

	<div class="well mt-4">
		<h4 class="text-info text-uppercase">{{ tr('Pi Control') }}</h4>
		<button type="button" class="btn btn-warning mr-2" @click="rebootPi" :disabled="loading">{{ tr('Reboot') }}</button>
		<button type="button" class="btn btn-danger" @click="haltPi" :disabled="loading">{{ tr('Shutdown') }}</button>
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
		serviceCssClass: function(svc) {
			if (!svc) return 'masked';
			if (svc.loadstate === 'Not-found' || svc.loadstate === 'Masked') return 'masked';
			return this.isServiceRunning(svc) ? 'success' : 'danger';
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
					snackbar(tr('Refresh failed'));
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
			if (row.type === 'text' || !row.type) return row.value || '';
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
