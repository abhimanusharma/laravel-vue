<template>
<div>
	<md-dialog-confirm
      :md-active.sync="confirmBox"
      md-title="Are you sure you want to delete?"
      md-content=""
      md-confirm-text="Yes"
      md-cancel-text="No"
      @md-cancel="onCancel"
      @md-confirm="onConfirm" />
    <div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100 email-listing">
		<md-card class="md-card-plain md-card-custom custom-shadow radius-10 mt-0">
			<md-card-content class="px-0 py-0 height-auto radius-10">
				<div class="without-header">
					<md-table v-model="messages" @md-selected="onSelectOne">
						<md-table-row slot="md-table-row" slot-scope="{ item }" md-auto-select md-selectable="multiple">
							<md-table-cell md-sort-by="id" class="text-left ml-2">
								<router-link :to="`${currentFolder}/${item.thread_id}`">
									&nbsp;<span class="text-primary" :class="{'light-weight': !item.is_unread}"> {{ utf8_decode(item.from) }}</span>
								</router-link>
							</md-table-cell>
							<md-table-cell md-sort-by="type" class="text-left">
								<router-link :to="`${currentFolder}/${item.thread_id}`">
									<span class="text-primary" :class="{'light-weight': !item.is_unread}">{{ utf8_decode(item.subject) }}</span>
								</router-link>
							</md-table-cell>
							<md-table-cell md-sort-by="contractDate" class="text-left">
								<span class="text-primary" :class="{'light-weight': !item.is_unread}">{{ item.date }}</span>
							</md-table-cell>
							<md-table-cell class="text-left">
								<md-menu md-direction="bottom-end">
									<md-button md-menu-trigger class="md-icon-button icon-button-flat">
										<md-icon>more_vert</md-icon>
									</md-button>
									<md-menu-content class="accordion-dropdown-content">
										<md-menu-item @click="viewEmail(item)">
											<md-icon class="accordion-icon">remove_red_eye</md-icon>
											View
										</md-menu-item>
										<md-menu-item v-if="type == 'DRAFT'">
											<md-icon class="accordion-icon">edit</md-icon>
											Edit
										</md-menu-item>
										<md-menu-item @click="confirmAction({item: item, action: 'trash'})" v-if="permission && permissionDelete">
											<md-icon class="accordion-icon">delete</md-icon>
											Delete
										</md-menu-item>
									</md-menu-content>
								</md-menu>
							</md-table-cell>
						</md-table-row>
					</md-table>
					<div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-50"
					v-if="totalPage > 1">
					<!-- for pagination you can check vue material kit -->
					<ul class="pagination">
						<li class="page-item prev-page" v-if="pagination.current_page" v-bind:class="[pagination.page == pagination.current_page ? 'active' : '']">
							<a @click="prevPage" class="page-link" aria-label="Previous">
								<i class="fas fa-angle-double-left"></i>
								<span class="pr-1">Prev</span>
							</a>
						</li>

						<li class="page-item page-pre next-page" v-if="pagination.page != ''"
							v-bind:class="[pagination.page == pagination.current_page ? 'active' : '']">
							<a @click="nextPage" class="page-link" aria-label="Next">
								<span class="pl-1">Next page</span>
								<i class="fas fa-angle-double-right"></i>
							</a>
						</li>
					</ul>
					</div>
					<div class="text-center p-4" v-if="!app.loading && messages && messages.length <= 0 && app.gmailAuth">
                        <img src="/img/no-data.svg" style="max-width:400px;" class="mb-2" v-show="!app.loading" />
					</div>
				</div>
			</md-card-content>
		</md-card>
    </div>
</div>
</template>

<script>
import { HTTP } from '../../httpCommon';

export default {
	props: ["app", "email", "transaction_code", "allowEdit"],
	data() {
		return {
			messages: [],
			currentFolder: '',
			confirmBox: false,
			selectedItem: {},
			action: '',

			pagination: {
				current_page: '',
				page: '',
				per_page: 10,
				total: ''
			},
			pages: [],
		};
	},
	computed: {
		type: function() {
			return this.$route.params.email_type;
		},
		transaction_id: function() {
			return this.$route.params.transaction_id ? this.$route.params.transaction_id : '';
		},
		totalPage() {
			return Math.ceil(this.pagination.total / parseInt(this.pagination.per_page));
		},
		permission() {
			if (typeof this.allowEdit !== "undefined") {
				return (
					this.$can("transactions-edit") &&
					this.allowEdit
				);
			} else {
				return this.$can("email-edit");
			}
		},
		permissionDelete() {
			return this.$can("email-delete");
		},
	},
	watch: {
		type: function(value) {
			this.init();
		}
	},
	created() {
		this.init();
	},
  	methods: {
		init() {
			this.app.loading = true;
			let params = {
				params: {
					transaction_code: this.transaction_code,
					transaction_id: this.transaction_id,
					per_page: 10,
					page: this.pagination.page,
				},
			};
			HTTP.get(`api/gmail/${this.type}`, params)
			.then(response => {
				this.messages = response.data.data.messages;
				this.app.gmailTotalUnread = response.data.data.unread_number;
				let currentFolder = response.data.data.current_folder;
				this.pagination = response.data.data.meta;
				if(this.pages.indexOf(this.pagination.page) === -1) {
					this.pages.push(this.pagination.page);
				}
				
				if (this.transaction_id != '') {
					this.currentFolder = `/transactions/${this.transaction_id}/emails/${currentFolder}`;
				} else {
					this.currentFolder = `/emails/${currentFolder}`;
				}
				this.app.loading = false;
				this.app.mainLoader = false;
			})
			.catch(error => {
				this.app.loading = false;
				this.app.gmailAuth = false;
				if(this.transaction_id != '') {
					this.$router.push({path: `/transactions/${this.transaction_id}/emails`});
				} else {
					if(this.type != "") {
						this.$router.push({path: `/emails`});
					}
				}
				console.log(error);
			})
		},
		confirmAction(data) {
			this.selectedItem = data.item;
			this.action = data.action;
			this.confirmBox = true;
		},
		onCancel() {
			this.confirmBox = false;
		},
		onConfirm() {
			if(this.action == 'trash') {
				this.trash(this.selectedItem);
			}
		},
		trash(item) {
			console.log(item);
			HTTP.delete(`api/gmail/${this.type}/${item.thread_id}/delete`)
			.then(response => {
				this.init();
				this.$toasted.show(response.data.data.Message, {
					type: 'success'
				}).goAway(1500);
			})
			.catch(error => {
				console.log(error);
			})
		},
		viewEmail(item) {
			this.$router.push({path: `${this.currentFolder}/${item.thread_id}`});
		},
    	onSelectOne() {},
		nextPage() {
			this.pagination.current_page = this.pagination.page;
			this.init();
		},
		prevPage() {
			let index = this.pages.length - 1;
			if(index <= 1) {
				this.pagination.page = '';
			} else if(this.pagination.page === ''){
				this.pagination.page = this.pages[index-2];
				this.pages = [];
			}else{
				this.pagination.page = this.pages[index];
			}
			
			this.init();
		},
  	},
};
</script>

<style lang="scss" scoped>
.md-card-custom .md-card-content {
    height: auto;
}

@media(max-width:768px) {
	.inbox-content-width {
		width: calc(100%);
		min-width: calc(100%);
		max-width: calc(100%);
	}
}
.preloader {
  width: 25px;
  height: 25px;
}
</style>