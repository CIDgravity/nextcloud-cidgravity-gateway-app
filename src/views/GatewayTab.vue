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
			<ul v-if="!loading" style="margin-top: 30px;">
				<strong>
					<h3>{{ getMetadataSectionTitle }}</h3>
				</strong>

				<!-- Display file CID -->
				<TabLinkEntrySimple ref="cidEntry"
					class="menu-entry__internal"
					:title="t('cidgravitygateway', 'CID')"
					:subtitle="shortenedCid">
					<template #avatar>
						<NcIconSvgWrapper inline :path="mdiIdentifier" />
					</template>

					<NcActionButton :title="t('cidgravitygateway', 'Copy CID')"
						:aria-label="t('cidgravitygateway', 'Copy CID')"
						@click="copyCid">
						<template #icon>
							<NcIconSvgWrapper inline :path="mdiContentCopy" />
						</template>
					</NcActionButton>
				</TabLinkEntrySimple>

				<!-- Display file status -->
				<TabLinkEntrySimple ref="cidEntry"
					class="menu-entry__internal"
					:title="fileStatusTitle"
					:subtitle="fileStatusDescription">
					<template #avatar>
						<NcIconSvgWrapper inline :path="mdiListStatus" />
					</template>
				</TabLinkEntrySimple>

				<!-- Display file retrievable copies -->
				<TabLinkEntrySimple ref="cidEntry"
					class="menu-entry__internal"
					:title="fileRetrievableCopiesTitle"
					:subtitle="t('cidgravitygateway', 'Number of copies that can be retrieved from Filecoin.')">
					<template #avatar>
						<NcIconSvgWrapper inline :path="mdiCounter" />
					</template>
				</TabLinkEntrySimple>

				<div v-if="isLinkAvailable" style="margin-top: 30px;">
					<strong>
						<h3>{{ t('cidgravitygateway', 'IPFS Public Link') }}</h3>
					</strong>

					<!-- Display IPFS public link -->
					<TabLinkEntrySimple ref="ipfsPublicLinkEntry"
						class="menu-entry__internal"
						:title="t('cidgravitygateway', 'IPFS public link')"
						:subtitle="t('cidgravitygateway', 'Click to open')"
						:link="ipfsPublicLink"
						:has-copy-btn="true">
						<template #avatar>
							<NcIconSvgWrapper inline :path="mdiLink" />
						</template>

						<NcActionButton :close-after-click="true" @click="copyIpfsPublicLink">
							<template #icon>
								<CopyIcon :size="20" />
							</template>
							{{ t('cidgravitygateway', 'Copy public link') }}
						</NcActionButton>

						<NcActionButton :close-after-click="true" @click="useGatewayFromStorageConfig">
							<template #icon>
								<Tune :size="20" />
							</template>
							{{ t('cidgravitygateway', 'Use default storage gateway') }}
						</NcActionButton>

						<NcActionSeparator />

						<NcActionButton :close-after-click="true" @click="useGatewayPinata">
							{{ t('cidgravitygateway', 'Use pinata.cloud gateway') }}
						</NcActionButton>

						<NcActionButton :close-after-click="true" @click="useGatewayIpfsIo">
							{{ t('cidgravitygateway', 'Use ipfs.io gateway') }}
						</NcActionButton>

						<NcActionButton :close-after-click="true" @click="useGatewayDweb">
							{{ t('cidgravitygateway', 'Use dweb.link gateway') }}
						</NcActionButton>
					</TabLinkEntrySimple>
				</div>
			</ul>
		</div>
	</div>
</template>

<script>
import TabLinkEntrySimple from '../components/TabLinkEntrySimple.vue'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import AlertCircleOutlineIcon from 'vue-material-design-icons/AlertCircleOutline.vue'

import CopyIcon from 'vue-material-design-icons/ContentCopy.vue'

import Tune from 'vue-material-design-icons/Tune.vue'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'

import axios from 'axios'

import { generateOcsUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'

import { mdiLink, mdiListStatus, mdiCounter, mdiContentCopy, mdiIdentifier } from '@mdi/js'

export default {
	name: 'GatewayTab',

	components: {
		Tune,
		CopyIcon,
		NcActionSeparator,

		TabLinkEntrySimple,
		NcActionButton,
		NcEmptyContent,
		AlertCircleOutlineIcon,
		NcIconSvgWrapper,
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

	setup() {
		return {
			mdiLink,
			mdiListStatus,
			mdiCounter,
			mdiContentCopy,
			mdiIdentifier,
		}
	},

	data() {
		return {
			loading: true,
			sharingPermission: 'ALL',
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
			return this.t('cidgravitygateway', 'No metadata available')
		},
		errorMessage() {
			return this.isErrorMessageLocal
		},
		getMetadataSectionTitle() {
			if (this.fileInfo.type === 'dir') {
				return this.t('cidgravitygateway', 'Directory details')
			} else {
				return this.t('cidgravitygateway', 'File details')
			}
		},
		emptyContentDescription() {
			const contentType = this.fileInfo.type === 'dir' ? 'directory' : 'file'
			return this.t('cidgravitygateway', 'This {contentType} is not located on a CIDgravity external storage.', { contentType })
		},
		shortenedCid() {
			if (this.fileMetadata.cid !== null && this.fileMetadata.cid !== '' && this.fileMetadata.cid !== undefined) {
				if (this.fileMetadata.cid.length > 15) {
					return (
						this.fileMetadata.cid.substring(0, 5)
						+ ' [...] '
						+ this.fileMetadata.cid.substring(this.fileMetadata.cid.length - 5, this.fileMetadata.cid.length)
					)
				} else {
					return this.fileMetadata.cid
				}
			} else {
				return this.fileMetadata.cid
			}
		},
		fileStatusTitle() {
			const title = this.t('cidgravitygateway', 'Status') + ': '

			switch (this.fileMetadata.details.state) {
			case 'staging':
				return title + this.t('cidgravitygateway', 'Staging')
			case 'offloading':
				return title + this.t('cidgravitygateway', 'Offloading')
			case 'partially_offloaded':
				return title + this.t('cidgravitygateway', 'Partially offloaded')
			case 'offloaded':
				return title + this.t('cidgravitygateway', 'Offloaded')
			default:
				return title + this.t('cidgravitygateway', 'Unknown')
			}
		},
		fileStatusDescription() {
			switch (this.fileMetadata.details.state) {
			case 'staging':
				return this.t('cidgravitygateway', 'Preparing to push to Filecoin.')
			case 'offloading':
				return this.t('cidgravitygateway', 'Actively transferring to Filecoin.')
			case 'partially_offloaded':
				return this.t('cidgravitygateway', 'At least one copy is stored and accessible on Filecoin.')
			case 'offloaded':
				return this.t('cidgravitygateway', 'All expected copies are stored and distributed on Filecoin.')
			default:
				return ''
			}
		},
		fileRetrievableCopiesTitle() {
			if (this.fileMetadata.details.retrievableCopies > 1) {
				return this.fileMetadata.details.retrievableCopies + ' ' + t('cidgravitygateway', 'retrievable copies')
			}

			return this.fileMetadata.details.retrievableCopies + ' ' + t('cidgravitygateway', 'retrievable copy')
		},
		ipfsPublicLink() {
			return this.ipfsGateway + '/' + this.fileMetadata.cid
		},
		isLinkAvailable() {
			return this.fileMetadata.details.state === 'partially_offloaded' || this.fileMetadata.details.state === 'offloaded'
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
		useGatewayFromStorageConfig() {
			this.ipfsGateway = this.externalStorageConfiguration.default_ipfs_gateway
			showSuccess(t('cidgravitygateway', 'IPFS gateway updated'))
		},
		useGatewayPinata() {
			this.ipfsGateway = 'https://gateway.pinata.cloud/ipfs'
			showSuccess(t('cidgravitygateway', 'IPFS gateway updated'))
		},
		useGatewayIpfsIo() {
			this.ipfsGateway = 'https://ipfs.io/ipfs'
			showSuccess(t('cidgravitygateway', 'IPFS gateway updated'))
		},
		useGatewayDweb() {
			this.ipfsGateway = 'https://dweb.link/ipfs'
			showSuccess(t('cidgravitygateway', 'IPFS gateway updated'))
		},
		setLoading(loading) {
			this.loading = loading
		},
		async copyCid() {
			try {
				await navigator.clipboard.writeText(this.fileMetadata.cid)
				showSuccess(t('cidgravitygateway', 'CID copied'))
			} catch (error) {
				showError(t('cidgravitygateway', 'Unable to copy the CID'))
				console.error(error)
			}
		},
		async copyIpfsPublicLink() {
			try {
				const publicLink = this.ipfsGateway + '/' + this.fileMetadata.cid
				await navigator.clipboard.writeText(publicLink)
				showSuccess(t('cidgravitygateway', 'Public link copied link copied'))
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
			this.ipfsGateway = this.externalStorageConfiguration.default_ipfs_gateway
		},
		loadFileMetadata() {
			axios.get(generateOcsUrl('apps/cidgravitygateway/get-file-metadata?fileId=' + this.fileInfo.id, 2)).then(res => {
				if (res.data.success) {
					this.fileMetadata = res.data.metadata.file
					this.isCidgravityStorageLocal = true
					this.isErrorLocal = false
					this.loading = false
					this.isErrorMessageLocal = null
				} else {
					console.error('unable to load file metadata')
					this.fileMetadata = {}
					this.loading = false
					this.isErrorLocal = true
					this.isErrorMessageLocal = res.data.error
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
