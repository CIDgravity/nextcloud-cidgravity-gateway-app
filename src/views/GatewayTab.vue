<template>
	<div>
		IPFS Link and file status here
	</div>
</template>

<script>

export default {
	name: 'GatewayTab',

	components: {
	},

	data() {
		return {
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
	},
}
</script>

<style scoped>
#tab-gatewaytab {
	height: 100%;
	padding: 0;
}
</style>
