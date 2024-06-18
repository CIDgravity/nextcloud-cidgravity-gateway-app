<template>
	<div>
		FileInfo: {{ fileInfo }}
		<br><br>
		External storage config: {{ externalStorageConfiguration }}
	</div>
</template>

<script>
export default {
	name: 'GatewayTab',

	components: {
	},

	data() {
		return {
			fileInfo: {},
			externalStorageConfiguration: {},
		}
	},

	computed: {
		/**
		 * Returns the current active tab
		 * needed because AppSidebarTab also uses $parent.activeTab
		 *
		 * @return {string}
		 */
		activeTab() {
			return this.$parent.activeTab
		},
	},

	beforeDestroy() {
		try {
			this.tab.$destroy()
		} catch (error) {
			console.error('Unable to unmount CidgravityGatewayTab', error)
		}
	},

	methods: {
		setFileInfo(fileInfo) {
			this.fileInfo = fileInfo
		},
		setExternalStorageConfiguration(config) {
			this.externalStorageConfiguration = config
		}
	},
}
</script>

<style scoped>
#tab-gatewaytab {
	height: 100%;
	padding: 0;
}
</style>
