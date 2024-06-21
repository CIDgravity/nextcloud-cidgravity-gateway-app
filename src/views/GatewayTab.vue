<template>
	<div class="tabContent" :class="{ 'icon-loading': loading }">
		<!-- Not a CIDgravity storage, nothing to display -->
		<NcEmptyContent v-if="!isCidgravityStorage && !isError"
			class="emptyContent"
			:name="emptyContentTitle"
			:description="emptyContentDescription">
			<template #icon>
				<AlertCircleOutlineIcon />
			</template>
		</NcEmptyContent>

		<!-- Error loading storage type -->
		<NcEmptyContent v-if="isError"
			class="emptyContent"
			:name="t('cidgravitygateway', 'Something wrong while loading metadata')">
			<template #icon>
				<AlertCircleOutlineIcon />
			</template>
		</NcEmptyContent>

		<!-- CIDgravity storage: display all metadata -->
		<div v-if="isCidgravityStorage && !isError">
			<div class="ipfs-gateway-select">
				<strong>
					<h3>{{ t('cidgravitygateway', 'Choose an IPFS gateway to use for links') }}</h3>
				</strong>

				<NcSelect ref="select"
					v-model="ipfsGateway"
					input-id="ipfs-gateway-input"
					class="ipfs-gateway__input"
					:loading="loading"
					:placeholder="t('cidgravitygateway', 'Choose an IPFS gateway to use for links')"
					:options="ipfsGatewayOptions"
					@option:selected="onIpfsGatewaySelected" />
			</div>

			<div v-if="isCustomIpfsGateway" class="ipfs-custom-gateway-input">
				<input v-model="ipfsGateway.link"
					input-id="ipfs-custom-gateway-input"
					:placeholder="t('cidgravitygateway', 'Type your custom IPFS gateway link, ending with /ipfs')"
					type="text"
					style="width: 100%; margin-top: 10px; margin-bottom: 10px;">
			</div>

			<ul v-if="!loading" style="margin-top: 30px;">
				<strong>
					<h3>{{ getMetadataSectionTitle }}</h3>
				</strong>

				<TabLinkEntrySimple ref="ipfsPublicLinkEntry"
					class="menu-entry__internal"
					:title="t('cidgravitygateway', 'IPFS public link')"
					:subtitle="t('cidgravitygateway', 'Click to open the IPFS link')"
					:link="ipfsPublicLink">
					<template #avatar>
						<div class="entry-icon-primary icon-public-white" />
					</template>

					<NcActionButton :title="t('cidgravitygateway', 'Copy public link')"
						:aria-label="t('cidgravitygateway', 'Copy public link')"
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
				{ id: 'gateway.pinata.cloud', label: 'gateway.pinata.cloud', link: 'https://gateway.pinata.cloud/ipfs', isCustom: false },
				{ id: 'ipfs.io', label: 'ipfs.io', link: 'https://ipfs.io/ipfs', isCustom: false },
				{ id: 'dweb.link', label: 'dweb.link', link: 'https://dweb.link/ipfs', isCustom: false },
				{ id: 'Custom gateway', label: 'Custom gateway', link: null, isCustom: true },
			],
			selectedOption: null,
			fileInfo: {},
			fileMetadata: {},
			externalStorageConfiguration: {},
			ipfsGateway: {},
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
			if (this.fileInfo && this.fileInfo.mountType === 'external') {
				return this.t('cidgravitygateway', 'Not on an external storage')
			}

			return this.t('cidgravitygateway', 'Not on an CIDgravity storage')
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

			if (this.fileInfo && this.fileInfo.mountType === 'external') {
				return this.t('cidgravitygateway', 'This {contentType} is not on an external storage. To display metadata, you must browse files on external storage', { contentType })
			}

			return this.t('cidgravitygateway', 'This {contentType} is not on an CIDgravity storage type. To display metadata, you must browse files on external storage', { contentType })
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
		ipfsPublicLink() {
			return this.ipfsGateway.link + '/' + this.fileMetadata.cid
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
				await navigator.clipboard.writeText(this.fileMetadata.cid)
				showSuccess(t('cidgravitygateway', 'CID copied'))
			} catch (error) {
				showError(t('cidgravitygateway', 'Unable to copy the CID'))
				console.error(error)
			}
		},
		async copyIpfsPublicLink() {
			try {
				const publicLink = this.ipfsGateway.link + '/' + this.fileMetadata.cid
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
			this.$emit('update:isCidgravityStorage', isCidgravityStorage)
		},
		setIsError(isError) {
			this.$emit('update:isError', isError)
		},
		setExternalStorageConfiguration(config) {
			this.externalStorageConfiguration = config
			this.$emit('update:isCidgravityStorage', true)

			// parse default ipfs gateway to get hostname only
			// if not in options, set to custom value
			const parsedUrl = new URL(this.externalStorageConfiguration.default_ipfs_gateway)

			if (this.ipfsGatewayOptions.some(e => e.link === this.externalStorageConfiguration.default_ipfs_gateway)) {
				this.ipfsGateway = {
					id: parsedUrl.hostname,
					label: parsedUrl.hostname,
					link: this.externalStorageConfiguration.default_ipfs_gateway,
				}
			} else {
				this.ipfsGateway = {
					id: 'Custom gateway',
					label: 'Custom gateway',
					link: this.externalStorageConfiguration.default_ipfs_gateway,
				}
			}
		},
		loadFileMetadata() {
			axios.get(generateOcsUrl('apps/cidgravitygateway/get-file-metadata?fileId=' + this.fileInfo.id, 2)).then(res => {
				if (res.data.success) {
					this.fileMetadata = res.data.metadata
					this.$emit('update:isCidgravityStorage', true)
					this.$emit('update:isError', false)
					this.loading = false
				}
			}).catch((error) => {
				console.error(error)
				this.loading = false
				this.$emit('update:isError', true)
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

.ipfs-custom-gateway-input {
	display: flex;
	flex-direction: column;
	margin-bottom: 10px;

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
