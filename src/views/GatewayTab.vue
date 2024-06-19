<template>
	<div>
		FileInfo: {{ fileInfo }}
		<br><br>
		External storage config: {{ externalStorageConfiguration }}
		<br><br>
		File metadata: {{ fileMetadata }}
	</div>
</template>

<script>
import axios from 'axios'

import { generateOcsUrl } from '@nextcloud/router'

export default {
	name: 'GatewayTab',

	components: {
	},

	data() {
		return {
			fileInfo: {},
			fileMetadata: {},
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
		},
		loadFileMetadata() {
			console.log(this.externalStorageConfiguration.metadata_endpoint)

			if (this.externalStorageConfiguration.metadata_endpoint) {
				axios.get(generateOcsUrl('apps/cidgravitygateway/get-file-metadata?fileId=' + this.fileInfo.id, 2)).then(res => {
					console.log(res.data)
					if (res.data.success) {
						this.fileMetadata = res.data.metadata
					}
				}).catch((error) => {
					console.error(error)
				})
			} else {
				console.error("unable to find metadata endpoint to call")
			}
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
