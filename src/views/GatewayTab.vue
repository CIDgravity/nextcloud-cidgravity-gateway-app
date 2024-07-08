<template>
	<div class="tabContent" :class="{ 'icon-loading': loading }">
		<!-- Not a CIDgravity storage, nothing to display -->
		<NcEmptyContent v-if="!isCidgravityStorageLocal"
			class="emptyContent"
			:name="emptyContentTitle"
			:description="emptyContentDescription">
			<template #icon>
				<AlertCircleOutlineIcon />
			</template>
		</NcEmptyContent>

		<!-- Error loading storage type -->
		<NcEmptyContent v-else-if="isErrorLocal"
			class="emptyContent"
			:name="t('cidgravitygateway', 'Something went wrong while loading metadata')"
			:description="errorMessage">
			<template #icon>
				<AlertCircleOutlineIcon />
			</template>
		</NcEmptyContent>

		<!-- CIDgravity storage: display all metadata -->
		<div v-else>
			<div class="ipfs-gateway-select">
				<strong>
					<h3>{{ t('cidgravitygateway', 'Select an IPFS gateway') }}</h3>
				</strong>

				<NcSelect ref="select"
					v-model="ipfsGateway"
					input-id="ipfs-gateway-input"
					class="ipfs-gateway__input"
					:loading="loading"
					:placeholder="t('cidgravitygateway', 'Select an IPFS gateway')"
					:options="ipfsGatewayOptions"
					@option:selected="onIpfsGatewaySelected" />
			</div>

			<ul v-if="!loading" style="margin-top: 30px;">
				<strong>
					<h3>{{ getMetadataSectionTitle }}</h3>
				</strong>

				<TabLinkEntrySimple ref="ipfsPublicLinkEntry"
					class="menu-entry__internal"
					:title="t('cidgravitygateway', 'IPFS public link')"
					:subtitle="t('cidgravitygateway', 'Click to open')"
					:link="ipfsPublicLink">
					<template #avatar>
						<div class="entry-icon-primary icon-public-white" />
					</template>

					<NcActionButton :title="t('cidgravitygateway', 'Copy link')"
						:aria-label="t('cidgravitygateway', 'Copy link')"
						@click="copyIpfsPublicLink">
						<template #icon>
							<ClipboardIcon :size="20" />
						</template>
					</NcActionButton>
				</TabLinkEntrySimple>

				<TabLinkEntrySimple ref="cidEntry"
					class="menu-entry__internal"
					:title="t('cidgravitygateway', 'CID')"
					:subtitle="shortenedCid">
					<template #avatar>
						<div class="entry-icon icon-triangle-e-white" />
					</template>

					<NcActionButton :title="t('cidgravitygateway', 'Copy CID')"
						:aria-label="t('cidgravitygateway', 'Copy CID')"
						@click="copyCid">
						<template #icon>
							<ClipboardIcon :size="20" />
						</template>
					</NcActionButton>
				</TabLinkEntrySimple>
			</ul>
		</div>
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import TabLinkEntrySimple from '../components/TabLinkEntrySimple.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import ClipboardIcon from 'vue-material-design-icons/ContentCopy.vue'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import AlertCircleOutlineIcon from 'vue-material-design-icons/AlertCircleOutline.vue'

import axios from 'axios'
import { generateOcsUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'GatewayTab',

	components: {
		NcSelect,
		TabLinkEntrySimple,
		NcActionButton,
		NcEmptyContent,
		ClipboardIcon,
		AlertCircleOutlineIcon,
	},

	props: {
		isCidgravityStorage: {
			type: Boolean,
			required: true,
		},
		isError: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			loading: true,
			ipfsGatewayOptions: [
				{ id: 'gateway.pinata.cloud', label: 'gateway.pinata.cloud', link: 'https://gateway.pinata.cloud/ipfs' },
				{ id: 'ipfs.io', label: 'ipfs.io', link: 'https://ipfs.io/ipfs' },
				{ id: 'dweb.link', label: 'dweb.link', link: 'https://dweb.link/ipfs' },
			],
			selectedOption: null,
			fileInfo: {},
			fileMetadata: {},
			externalStorageConfiguration: {},
			ipfsGateway: {},
			isErrorLocal: this.isError,
			isErrorMessageLocal: null,
			isCidgravityStorageLocal: this.isCidgravityStorage,
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
		emptyContentTitle() {
			return this.t('cidgravitygateway', 'Not metadata available')
		},
		errorMessage() {
			return this.isErrorMessageLocal
		},
		getMetadataSectionTitle() {
			if (this.fileInfo.type === 'dir') {
				return this.t('cidgravitygateway', 'Directory metadata')
			} else {
				return this.t('cidgravitygateway', 'File metadata')
			}
		},
		emptyContentDescription() {
			const contentType = this.fileInfo.type === 'dir' ? 'directory' : 'file'
			return this.t('cidgravitygateway', 'This {contentType} is not located on a CIDgravity external storage.', { contentType })
		},
		shortenedCid() {
			if (this.fileMetadata.result.file.cid !== null && this.fileMetadata.result.file.cid !== '' && this.fileMetadata.result.file.cid !== undefined) {
				if (this.fileMetadata.result.file.cid.length > 15) {
					return (
						this.fileMetadata.result.file.cid.substring(0, 5)
						+ ' [...] '
						+ this.fileMetadata.result.file.cid.substring(this.fileMetadata.result.file.cid.length - 5, this.fileMetadata.result.file.cid.length)
					)
				} else {
					return this.fileMetadata.result.file.cid
				}
			} else {
				return this.fileMetadata.result.file.cid
			}
		},
		ipfsPublicLink() {
			return this.ipfsGateway.link + '/' + this.fileMetadata.result.file.cid
		},
		isCustomIpfsGateway() {
			return this.ipfsGateway.isCustom
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
		setLoading(loading) {
			this.loading = loading
		},
		onIpfsGatewaySelected(option) {
			this.ipfsGateway = option
		},
		async copyCid() {
			try {
				await navigator.clipboard.writeText(this.fileMetadata.result.file.cid)
				showSuccess(t('cidgravitygateway', 'CID copied'))
			} catch (error) {
				showError(t('cidgravitygateway', 'Unable to copy the CID'))
				console.error(error)
			}
		},
		async copyIpfsPublicLink() {
			try {
				const publicLink = this.ipfsGateway.link + '/' + this.fileMetadata.result.file.cid
				await navigator.clipboard.writeText(publicLink)
				showSuccess(t('cidgravitygateway', 'Public link copied'))
			} catch (error) {
				showError(t('cidgravitygateway', 'Unable to copy the public link'))
				console.error(error)
			}
		},
		setFileInfo(fileInfo) {
			this.fileInfo = fileInfo
		},
		setIsCidgravityStorage(isCidgravityStorage) {
			this.isCidgravityStorageLocal = isCidgravityStorage
		},
		setIsError(isError, errorMessage) {
			this.isErrorLocal = isError
			this.isErrorMessageLocal = errorMessage
		},
		setExternalStorageConfiguration(config) {
			this.externalStorageConfiguration = config
			this.isCidgravityStorageLocal = true

			// only list of gateway, not custom value here
			const parsedUrl = new URL(this.externalStorageConfiguration.default_ipfs_gateway)

			if (this.ipfsGatewayOptions.some(e => e.link === this.externalStorageConfiguration.default_ipfs_gateway)) {
				this.ipfsGateway = {
					id: parsedUrl.hostname,
					label: parsedUrl.hostname,
					link: this.externalStorageConfiguration.default_ipfs_gateway,
				}
			}
		},
		loadFileMetadata() {
			axios.get(generateOcsUrl('apps/cidgravitygateway/get-file-metadata?fileId=' + this.fileInfo.id, 2)).then(res => {
				if (res.data.success) {
					this.fileMetadata = res.data.metadata
					this.isCidgravityStorageLocal = true
					this.isErrorLocal = false
					this.loading = false
					this.isErrorMessageLocal = null
				} else {
					console.error('unable to load file metadata')
					this.fileMetadata = {}
					this.loading = false
					this.isErrorLocal = true
					this.isErrorMessageLocal = res.data.metadata.error
				}
			}).catch((error) => {
				console.error(error)
				this.loading = false
				this.isErrorLocal = true
				this.isErrorMessageLocal = null
			})
		},
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

.emptyContent {
	margin-top: 50%;
	width: 100%;
	padding: 10px;
	text-align: center;
}

.ipfs-gateway-select {
	display: flex;
	flex-direction: column;
	margin-bottom: 10px;
	margin-top: 10px;

	label[for="ipfs-gateway-input"] {
		margin-bottom: 2px;
	}

	&__input {
		width: 100%;
		margin: 10px 0;
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

	.entry-icon-primary {
		width: 32px;
		height: 32px;
		line-height: 32px;
		font-size: 18px;
		background-color: var(--color-primary-element);
		border-radius: 50%;
		flex-shrink: 0;
	}

	.icon-checkmark-color {
		opacity: 1;
		color: var(--color-success);
	}
}
</style>
