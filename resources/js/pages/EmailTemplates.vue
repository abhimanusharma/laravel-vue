<template>
<div>
	<DeleteModal
      :active="deleteDialog"
      :app="app"
      :list-name="'Email Templates'"
      :selected-form="template"
      @dialogClose="deleteFolderDialog = false"
      @deleteForm="deleteItem"
    />
	<!-- Create email template dialog start here -->
	<md-dialog :md-active.sync="createEmailTemplateDialog" class="modal-medium new-message-modal">
		<md-dialog-title
		class="modal-title d-block py-3 px-4 bg-light position-relative"
		>
			<h4 class="my-0 md-headline text-left text-primary">Create email template</h4>
			<span
			@click="close"
			class="text-primary md-icon-button position-absolute close-btn"
			>
				<md-icon class="text-primary">close</md-icon>
			</span>
		</md-dialog-title>
		<div class="md-dialog-content p-0">
			<md-content md-dynamic-height class="px-4">
				<div class="md-layout">
					<div class="md-layout-item md-small-size-100 md-size-100">
						<md-field>
							<label>Template title</label>
							<md-input v-model="template.name"></md-input>
						</md-field>
					</div>
					<div class="md-layout-item md-small-size-100 md-size-100">
						<md-field>
							<label>Subject</label>
							<md-input v-model="template.subject"></md-input>
						</md-field>
					</div>
					<div class="md-layout-item md-small-size-100 md-size-100">
						<h4 class="md-title text-left text-primary">Email body:</h4>
						<vue-editor v-model="template.body"></vue-editor>
					</div>
				</div>
			</md-content>
		</div>
		<md-dialog-actions class="d-flex flex-wrap">
			<md-button
			:md-ripple="false"
			@click="close"
			class="cancel-link email-cancel-button lighter-description text-capitalize mr-auto"
			>Cancel</md-button
			>
			<md-button
			type="submit"
			class="md-button md-primary md-theme-default button-custom-regular"
			@click="saveTemplate"
			>
				<span>Save <LoadingButtonLoader :typeEnable="'simpleLoader'" v-bind:enable="app.loading" class="mr-1" /></span>
			</md-button>
		</md-dialog-actions>
	</md-dialog>
	<!-- Create email dialog end here -->
	<!-- Email Template page start here  -->
	<div class="md-layout teamplate-builder" v-if="app.gmailAuth">
		<div class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100">
			<md-card class="md-card-plain md-card-custom custom-shadow radius-10 mt-0">
				<md-card-header class="px-4 mb-0 mt-0">
					<div class="md-headline md-headline-with-progress d-flex align-center justify-content-between">
						<span class="text-primary">Email templates</span>
						<div class="searchbox ml-auto mr-3">
							<md-field md-inline>
								<span class="search-icon">
									<img src="/img/search-icon-template.svg" alt="img" />
								</span>
								<label>Search...</label>
								<md-input v-model="inline"></md-input>
							</md-field>
						</div>
						<div class="form-header-icons d-flex align-items-center">
							<md-button
							class="md-primary md-icon-button"
							@click="createEmailTemplateDialog = true"
							v-if="permission"
							>
								<md-icon>add</md-icon>
							</md-button>
						</div>
					</div>
				</md-card-header>
				<md-card-content class="px-4 py-4">
					<div class="md-layout align-center justify-content-center">
						<div
						class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100 text-center"
						>
							<div class="template-builder-table">
								<md-table
								v-model="templates"
								md-card
								@md-selected="onSelectOne"
								>
									<md-table-row
									slot="md-table-row"
									slot-scope="{ item }"
									md-selectable="multiple"
									md-auto-select
									>
										<md-table-cell
										md-label="Template tiltle"
										md-sort-by="name"
										style="width: 60%"
										>
											<span class="text-primary">{{
											item.name
											}}</span>
										</md-table-cell>
										<md-table-cell
										md-label="Last Updated On"
										md-sort-by="email"
										>
											<span class="text-primary">{{
											item.updated_at
											}}</span>
										</md-table-cell>
										<md-table-cell class="text-left">
											<md-menu md-direction="bottom-end">
												<md-button
												md-menu-trigger
												class="md-icon-button icon-button-flat"
												>
													<md-icon>more_vert</md-icon>
												</md-button>
												<md-menu-content class="accordion-dropdown-content">
													<md-menu-item @click="editTemplate(item)" v-if="permission">
														<md-icon class="accordion-icon">edit</md-icon>
														Edit
													</md-menu-item>
													<md-menu-item @click="onConfirm(item)" v-if="permission && permissionDelete">
														<md-icon class="accordion-icon">delete</md-icon>
														Delete
													</md-menu-item>
												</md-menu-content>
											</md-menu>
										</md-table-cell>
									</md-table-row>
								</md-table>
								<div class="md-layout">
									<div
									class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-50 d-flex align-center"
									>
									<p class="description font-weight-medium">
										Displaying <span v-if="templates">{{templates.length}}</span> of {{pagination.total}} records
									</p>
									</div>
									<div class="md-layout-item md-medium-size-50 md-xsmall-size-100 md-size-50"
									v-if="totalPage > 1">
									<!-- for pagination you can check vue material kit -->
										<paginate
											:page-count="totalPage"
											:click-handler="clickCallback"
											:prev-text="prevText"
											:next-text="nextText"
											:container-class="'pagination'">
										</paginate>
									</div>
								</div>
							</div>
						</div>
					</div>
				</md-card-content>
			</md-card>
		</div>
	</div>
	<!-- Email Template page end here  -->
</div>
</template>

<script>
import { HTTP } from "../../httpCommon";
import Paginate from "vuejs-paginate";
import { VueEditor } from "vue2-editor";
import DeleteModal from "./reuseComponents/DeleteModal";
import LoadingButtonLoader from "../../components/LoadingButtonLoader";

export default {
	props: ["app", "email", "allowEdit"],
	components: {
		Paginate,
		VueEditor,
		DeleteModal,
		LoadingButtonLoader,
	},
	data() {
		return {
			inline: null,
			createEmailTemplateDialog: false,
			template: {id:''},
			tags: null,
			single: null,
			templates: [],
			selectedItems: [],
			deleteDialog: false,
			nextText: 'Next page <i class="fas fa-angle-double-right"></i>',
			prevText: '<i class="fas fa-angle-double-left"></i> Prev',
			pagination: {
				per_page: 10,
				current_page: 1,
			},
			pageNum: 1,
		};
	},
	computed: {
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
	created() {
		this.init();
	},
	methods: {
		init() {
			this.app.loading = true;
			const data = {
				params: {
					per_page: 10,
					page: this.pageNum
				}
			}
			HTTP.get(`api/gmail/templates`, data)
			.then((response) => {
				this.templates = response.data.data;
				this.app.gmailTotalTemplates = response.data.meta.total;
				this.pagination = response.data.meta;
				this.app.loading = false;
				this.app.mainLoader = false;
			})
			.catch((error) => {
				console.log(error);
				this.app.loading = false;
			});
		},
		saveTemplate() {
			this.app.loading = true;
			const data = {
				id: this.template.id,
				name: this.template.name,
				subject: this.template.subject,
				body: this.template.body,
			};

			HTTP.post(`api/gmail/templates`, data)
			.then(response => {
				this.close();
				this.template = {};
				this.init();
				this.$root.$emit('refreshTemplates');
				this.$toasted.show(response.data.Message, {type: 'success'}).goAway(1500);
				this.app.loading = false;
			})
			.catch(error => {
				console.log(error);
			})
		},
		editTemplate(item) {
			this.template = item;
			this.createEmailTemplateDialog = true;
		},
		onConfirm(item) {
			this.template = item;
			this.deleteDialog = true;
		},
		deleteItem(id) {
			HTTP.delete(`api/gmail/templates/${id}`)
			.then(response => {
				console.log(response);
				this.deleteDialog = false;
				this.pageNum = 1;
				this.init();
				this.$toasted.show(response.data.Message, {type: 'success'}).goAway(1500);
			})
			.catch(error => {
				console.log(error);
				if(error.response) {
					this.$toasted.show(error.response.data, {type: 'error'}).goAway(1500);
				}
			})
		},
		onSelectOne(items) {
			this.selectedItems = items;
		},
		close() {
			this.createEmailTemplateDialog = false;
		},
		clickCallback(pageNum) {
			this.pageNum = pageNum;
			this.init();
			return pageNum;
		},
	},
};
</script>

<style lang="scss" scoped>
.back-arrow {
	color: #9191bc !important;
}
.md-avatar {
	&.updated-by-avatar {
		width: 35px;
		min-width: 35px;
		height: 35px;
		line-height: 35px;
		font-weight: normal;
		border: 3px solid #fff;
	}
	&.updated-on-avatar {
		width: 50px;
		min-width: 50px;
		height: 50px;
		line-height: 50px;
	}
}
.close-btn {
	right: 20px;
	cursor: pointer;
	width: 25px;
	min-width: 20px;
	height: 30px;
}

@media (max-width: 767px) {
  .searchbox {
    display: none;
  }
  .md-headline-with-progress {
    flex-direction: inherit;
}
.form-header-icons {
    margin-left: auto;
}

}
</style>