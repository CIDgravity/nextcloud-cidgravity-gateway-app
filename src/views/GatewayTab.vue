<template>
	<div class="tabContent" :class="{ 'icon-loading': loading }">
		<ul v-if="!loading">
			<TabLinkEntrySimple 
				ref="cidEntry" 
				class="menu-entry__internal" 
				:title="t('cidgravitygateway', 'File CID')"
				:subtitle="shortenedFileCid"
				>
				<template #avatar>
					<div class="entry-icon icon-external-white"></div>
				</template>

				<NcActionButton :title="t('cidgravitygateway', 'Copy file CID')" 
				:aria-label="t('cidgravitygateway', 'Copy file CID')" 
				@click="copyFileCid">
					<template #icon>
						<CheckIcon v-if="copied && copySuccess" :size="20" class="icon-checkmark-color" />
						<ClipboardIcon v-else :size="20" />
					</template>
				</NcActionButton>
			</TabLinkEntrySimple>

			<TabLinkEntrySimple 
				ref="ipfsPublicLinkEntry" 
				class="menu-entry__internal" 
				:title="t('cidgravitygateway', 'IPFS public link')"
				:subtitle="t('cidgravitygateway', 'Click to open the IPFS link')"
				>
				<template #avatar>
					<div class="entry-icon icon-external-white"></div>
				</template>

				<NcActionButton :title="t('cidgravitygateway', 'Copy public link')" 
				:aria-label="t('cidgravitygateway', 'Copy public link')" 
				@click="copyIpfsPublicLink">
					<template #icon>
						<CheckIcon v-if="copied && copySuccess" :size="20" class="icon-checkmark-color" />
						<ClipboardIcon v-else :size="20" />
					</template>
				</NcActionButton>
			</TabLinkEntrySimple>
		</ul>
	</div>
</template>

<script>
import TabLinkEntrySimple from '../components/TabLinkEntrySimple.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import ClipboardIcon from 'vue-material-design-icons/ContentCopy.vue'

import axios from 'axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'GatewayTab',

	components: {
		TabLinkEntrySimple,
		NcActionButton,
		CheckIcon,
		ClipboardIcon,
	},

	data() {
		return {
			loading: true,
			fileInfo: {},
			fileMetadata: {},
			externalStorageConfiguration: {},
			ipfsGateway: null,
			copied: false,
			copySuccess: false,
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
		shortenedFileCid() {
			if (this.fileMetadata.cid !== null && this.fileMetadata.cid !== '' && this.fileMetadata.cid !== undefined) {
				if (this.fileMetadata.cid.length > 15) {
					return (
						this.fileMetadata.cid.substring(0, 5) +
						' [...] ' +
						this.fileMetadata.cid.substring(this.fileMetadata.cid.length - 5, this.fileMetadata.cid.length)
					)
				} else {
					return this.fileMetadata.cid
				}
			} else {
				return this.fileMetadata.cid
			}
		},
		ipfsPublicLink() {
			return this.ipfsGateway + "/" + this.fileMetadata.cid
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
		async copyFileCid() {
			try {
				await navigator.clipboard.writeText(this.fileMetadata.cid)
				showSuccess(t('cidgravitygateway', 'CID copied'))
				this.$refs.cidEntry.$refs.actionsComponent.$el.focus()
				this.copySuccess = true
				this.copied = true
			} catch (error) {
				this.copySuccess = false
				this.copied = true
				console.error(error)
			} finally {
				setTimeout(() => {
					this.copySuccess = false
					this.copied = false
				}, 2000)
			}
		},
		async copyIpfsPublicLink() {
			try {
				const publicLink = this.ipfsGateway + "/" + this.fileMetadata.cid
				await navigator.clipboard.writeText(publicLink)
				showSuccess(t('cidgravitygateway', 'Public link copied'))
				this.$refs.ipfsPublicLinkEntry.$refs.actionsComponent.$el.focus()
				this.copySuccess = true
				this.copied = true
			} catch (error) {
				this.copySuccess = false
				this.copied = true
				console.error(error)
			} finally {
				setTimeout(() => {
					this.copySuccess = false
					this.copied = false
				}, 2000)
			}
		},
		setFileInfo(fileInfo) {
			this.fileInfo = fileInfo
		},
		setExternalStorageConfiguration(config) {
			this.externalStorageConfiguration = config
			this.ipfsGateway = config.default_ipfs_gateway
		},
		loadFileMetadata() {
			axios.get(generateOcsUrl('apps/cidgravitygateway/get-file-metadata?fileId=' + this.fileInfo.id, 2)).then(res => {
				if (res.data.success) {
					this.fileMetadata = res.data.metadata
					this.loading = false
				}
			}).catch((error) => {
				console.error(error)
				this.loading = false
			})
		}
	},
}
</script>

<style scoped>
.tabContent {
	position: relative;
	height: 100%;

	&__content {
		padding: 0 6px;
	}

	&__additionalContent {
		margin: 44px 0;
	}
}

.menu-entry__internal {
	.entry-icon {
		width: 32px;
		height: 32px;
		line-height: 32px;
		font-size: 18px;
		background-color: var(--color-text-maxcontrast);
		border-radius: 50%;
		flex-shrink: 0;
	}
	.icon-checkmark-color {
		opacity: 1;
		color: var(--color-success);
	}
}
</style>
