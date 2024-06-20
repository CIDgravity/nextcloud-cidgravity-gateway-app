import Vue from 'vue'
import axios from '@nextcloud/axios'

import { generateOcsUrl } from '@nextcloud/router'
import { translate, translatePlural } from 'nextcloud-l10n'

import GatewayTab from './views/GatewayTab.vue'
import CustomIcon from './custom-icon.svg' // Import your custom SVG icon

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

const View = Vue.extend(GatewayTab)
let tabInstance = null

window.addEventListener('DOMContentLoaded', function() {
	if (OCA.Files && OCA.Files.Sidebar) {
		const gatewayTab = new OCA.Files.Sidebar.Tab({
			id: 'cidgravitygateway',
			name: t('cidgravitygateway', 'CIDgravity'),
			icon: CustomIcon,
			iconSvg: CustomIcon,
			
			mount(el, fileInfo, context) {
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
									propsData: {
										isCidgravityStorage: true,
										isError: false,
									},
								})
								
								tabInstance.setExternalStorageConfiguration(response.data.configuration)
								tabInstance.setFileInfo(fileInfo)	
								tabInstance.loadFileMetadata()	
								tabInstance.$mount(el)	
							}
						}

					}).catch((error) => {
						console.error(error)

						tabInstance = new View({
							parent: context,
							propsData: {
								isCidgravityStorage: false,
								isError: true,
							},
						})

						tabInstance.$mount(el)
					})

				} else {
					tabInstance = new View({
						parent: context,
						propsData: {
							isCidgravityStorage: false,
							isError: false,
						},
					})

					tabInstance.$mount(el)
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

		OCA.Files.Sidebar.registerTab(gatewayTab)
	}
})
