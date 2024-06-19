import Vue from 'vue'
import axios from '@nextcloud/axios'

import { generateOcsUrl } from '@nextcloud/router'
import { translate, translatePlural } from 'nextcloud-l10n'

import GatewayTab from './views/GatewayTab.vue'

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

const View = Vue.extend(GatewayTab)
let tabInstance = null

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		const gatewayTab = new OCA.Files.Sidebar.Tab({
			id: 'cidgravitygateway',
			name: t('cidgravitygateway', 'CIDgravity'),
			icon: 'icon-rename',

			mount(el, fileInfo, context) {
				console.log('mount')
				if (tabInstance) {
					tabInstance.$destroy()
				}

				if (fileInfo && fileInfo.mountType === "external") {
					const url = generateOcsUrl('apps/cidgravitygateway/get-external-storage-config?fileId=' + fileInfo.id, 2)

					axios.get(url).then((response) => {
						if (response.data.success) {
							if (response.data.configuration.is_cidgravity) {
								tabInstance = new View({
									parent: context,
								})
								
								tabInstance.setExternalStorageConfiguration(response.data.configuration)
								tabInstance.setFileInfo(fileInfo)	
								tabInstance.loadFileMetadata()		
								tabInstance.$mount(el)
							} else {
								console.log('destroy because not an cidgravity storage')
							}
						}

					}).catch((error) => {
						console.error(error)
					})
				} else {
					console.log('destroy because not an external storage')
					tabInstance.$destroy()
				}
			},

			update(fileInfo) {
				tabInstance.setFileInfo(fileInfo)
			},

			destroy() {
				tabInstance.$destroy()
				tabInstance = null
			},
		})

		console.log('registerTab')
		OCA.Files.Sidebar.registerTab(gatewayTab)
	}
})
