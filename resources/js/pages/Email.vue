<template>
  <div>
    <DocumentUploader
    :active="uploadFileDialog"
    :app="app"
    :email="this"
    @dialogClose="handleDialogClose"
    @uploadFile="filesUploaded"
    ref="documentUploader"
    />
    <!-- New message dialog start here -->
    <md-dialog
      :md-active.sync="newMessageDialog"
      class="modal-large new-message-modal"
    >
      <md-dialog-title
        class="modal-title d-block py-3 px-4 bg-light position-relative"
      >
        <h4 class="my-0 md-headline text-left text-primary">New message</h4>
        <img src="/img/preloader.gif" class="ml-1 preloader" v-if="app.loading">
        <span
          @click="close"
          class="text-primary md-icon-button position-absolute close-btn"
        >
          <md-icon class="text-primary">close</md-icon>
        </span>
      </md-dialog-title>
      <form @submit.prevent="sendEmail" class="model-form mt-1">
      <div class="md-dialog-content p-0">
        <md-content md-dynamic-height class="px-4">
          <div class="md-layout">
            <div class="md-layout-item md-small-size-100 md-size-100">
              <md-field class="multi-error" :class="app.getValidationClass($v.message,'to')">
                <label>To</label>
                <md-input
                  v-model="message.to"
                  @focus="showccbcc = true"
                ></md-input>
                <span v-if="showccbcc"
                  ><span @click="toggleCc" class="cursor-pointer">CC</span>
                  <span @click="toggleBcc" class="cursor-pointer"
                    >BCC</span
                  ></span>
                  <span v-if="!$v.message.to.required" class="md-error">Recipient is required</span>
                  <span v-if="!$v.message.to.email" class="md-error">Email address is Invalid</span>
              </md-field>
            </div>
            <div
              class="md-layout-item md-small-size-100 md-size-100"
              v-if="showCc"
            >
              <md-field>
                <label>CC</label>
                <md-input
                  v-model="message.cc"
                  @focus="showCc = true"
                  @blur="showCc = false"
                ></md-input>
              </md-field>
            </div>
            <div
              class="md-layout-item md-small-size-100 md-size-100"
              v-if="showBcc"
            >
              <md-field>
                <label>BCC</label>
                <md-input
                  v-model="message.bcc"
                  @focus="showBcc = true"
                  @blur="showBcc = false"
                ></md-input>
              </md-field>
            </div>
            <div class="md-layout-item md-small-size-100 md-size-45">
              <md-field class="multi-error" :class="app.getValidationClass($v.message,'subject')">
                <label>Subject</label>
                <md-input
                  v-model="message.subject"
                  @focus="showccbcc = false"
                ></md-input>
                <span v-if="!$v.message.subject.required" class="md-error">Subject is required</span>
                <span v-if="!$v.message.subject.minLength" class="md-error">Subject is too small</span>
                <span v-if="!$v.message.subject.maxLength" class="md-error">Subject is too large</span>
              </md-field>
            </div>
            <div class="md-layout-item md-small-size-100 md-size-25">
              <md-field>
                <md-select
                  v-model="message.label"
                  name="tags"
                  id="tags"
                  placeholder="Tags"
                  @focus="showccbcc = false"
                >
                  <md-option value="">Tags</md-option>
                  <md-option value="#buyer">#buyer</md-option>
                  <md-option value="#seller">#seller</md-option>
                  <md-option value="#agent">#agent</md-option>
                </md-select>
              </md-field>
            </div>
            <div class="md-layout-item md-small-size-100 md-size-30">
              <md-field>
                <md-select
                  v-model="chooseTemplate"
                  name="chooseTemplate"
                  id="chooseTemplate"
                  placeholder="choose Template"
                  @focus="showccbcc = false"
                  @md-selected="changeTemplate"
                >
                  <md-option
                    v-for="template in templates"
                    :key="template.id"
                    :value="template.id"
                    >{{ template.name }}</md-option>
                </md-select>
              </md-field>
            </div>
            <div class="md-layout-item md-small-size-100 md-size-100">
              <md-field class="multi-error vue-editor" :class="app.getValidationClass($v.message,'body')">
                <vue-editor
                  v-model="message.body"
                  @focus="showccbcc = false"
                ></vue-editor>
                <span v-if="!$v.message.body.required" class="md-error">Body is required</span>
                <span v-if="!$v.message.body.minLength" class="md-error">Body is too small</span>
                <span v-if="!$v.message.body.maxLength" class="md-error">Body is too large</span>
              </md-field>
            </div>
            <div
              class="md-layout-item md-small-size-100 md-size-100 attachement-file"
            >
              <md-field class="md-has-file">
                <label>Attachement</label>
                <md-file v-model="message.attachments" @change="attachFiles" multiple/>
              </md-field>

              <div class="invoice-row d-flex flex-wrap" v-for="attachment in uploadedAttachments" :key="attachment.name">
                <span class="pdf-icon">
                  <img src="/img/invoice-pdf.svg" alt="img" />
                </span>
                <div class="invoice-content">
                  <h4 class="my-0 md-headline text-left text-primary">
                    {{ attachment.name }}
                  </h4>
                  <p class="description mb-0 mt-1">{{ bytesToHuman(attachment.size) }}</p>
                </div>
                <span @click="removeAttachment(attachment.name)">
                  <md-icon
                  class="cursor-pointer closed-label mr-3 closed-label close-label-circle description d-flex align-center justify-content-center"
                  >close</md-icon>
                </span>
              </div>
            </div>
          </div>
        </md-content>
      </div>
      <md-dialog-actions class="d-flex flex-wrap">
        <md-button
          type="submit"
          class="md-button md-theme-default button-custom-regular button-outline"
          @click="saveAsTemplate"
        >
          <span
            ><md-icon class="save-icon">save</md-icon> Save email as
            template</span
          >
        </md-button>

        <md-button
          :md-ripple="false"
          @click="close"
          class="cancel-link lighter-description text-capitalize ml-auto"
          >Cancel</md-button
        >

        <md-button
          type="submit"
          class="md-button md-primary md-theme-default button-custom-regular"
          :disabled="app.loading"
        >
          <span><md-icon class="send-icon">send</md-icon> Send</span>
        </md-button>
      </md-dialog-actions>
      </form>
    </md-dialog>
    <!-- New message dialog end here -->

    <!-- Main Email view start here -->
    <div class="d-flex">
      <h4 slot="title" class="card-title mt-0">Email</h4>
      <img src="/img/preloader.gif" class="m-1 preloader" v-if="app.loading">
    </div>
    <div class="md-layout">
      <div
        class="md-layout-item md-medium-size-35 md-xsmall-size-100 md-size-20 sidebar-custom-width"
        v-if="app.gmailAuth"
      >
        <md-card
          class="md-card-plain md-card-custom custom-shadow radius-10 mt-0 email-sidebar"
        >
          <div class="btn-area">
            <md-button
              v-if="permission"
              class="md-primary button-custom-regular"
              @click="newMessageDialog = true"
            >
              <md-icon class="mr-1 d-inline-block v-align-middle">add</md-icon
              >Compose
            </md-button>
          </div>

          <md-list class="v-list">
            <router-link
              :to="route.inbox"
              v-slot="{ href, navigate, isActive }"
            >
              <md-list-item :class="isActive && 'active'">
                <a
                  :href="href"
                  @click="navigate"
                  class="navigation-text-color w-100"
                >
                  <md-icon>move_to_inbox</md-icon>
                  <span
                    class="md-list-item-text text-primary ml-2 text-capitalize inbox-list-text"
                    >Inbox</span
                  >
                  <span class="total" v-show="isActive">{{ totalUnread }}</span>
                </a>
              </md-list-item>
            </router-link>

            <router-link
              :to="route.draft"
              v-slot="{ href, navigate, isActive }"
            >
              <md-list-item :class="isActive && 'active'">
                <a
                  :href="href"
                  @click="navigate"
                  class="navigation-text-color w-100"
                >
                  <md-icon>description</md-icon>
                  <span
                    class="md-list-item-text text-primary ml-2 text-capitalize inbox-list-text"
                    >Draft</span
                  >
                </a>
              </md-list-item>
            </router-link>

            <router-link :to="route.sent" v-slot="{ href, navigate, isActive }">
              <md-list-item :class="isActive && 'active'">
                <a
                  :href="href"
                  @click="navigate"
                  class="navigation-text-color w-100"
                >
                  <md-icon>send</md-icon>
                  <span
                    class="md-list-item-text text-primary ml-2 text-capitalize inbox-list-text"
                    >Sent Mail</span
                  >
                  <span class="total" v-show="isActive">{{ totalUnread }}</span>
                </a>
              </md-list-item>
            </router-link>

            <router-link
              :to="route.trash"
              v-slot="{ href, navigate, isActive }"
            >
              <md-list-item :class="isActive && 'active'">
                <a
                  :href="href"
                  @click="navigate"
                  class="navigation-text-color w-100"
                >
                  <md-icon>delete</md-icon>
                  <span
                    class="md-list-item-text text-primary ml-2 text-capitalize inbox-list-text"
                    >Trash</span
                  >
                  <span class="total" v-show="isActive">{{ totalUnread }}</span>
                </a>
              </md-list-item>
            </router-link>
            <hr />

            <md-list-item>
              <a class="navigation-text-color w-100">
                <span
                  class="md-list-item-text text-primary ml-2 text-capitalize inbox-list-text"
                  >Templates</span
                >
              </a>
            </md-list-item>

            <router-link
              :to="route.emailTemplates"
              v-slot="{ href, navigate, isActive }"
            >
              <md-list-item :class="isActive && 'active'">
                <a
                  :href="href"
                  @click="navigate"
                  class="navigation-text-color w-100"
                >
                  <span
                    class="md-list-item-text text-primary ml-2 text-capitalize inbox-list-text"
                    >All</span
                  >
                  <span class="total all-count text-primary">{{ app.gmailTotalTemplates }}</span>
                </a>
              </md-list-item>
            </router-link>
          </md-list>
        </md-card>
      </div>

      <div
        class="d-flex md-layout-item md-medium-size-80 md-xsmall-size-80 md-size-80 p-0 inbox-content-width flex-wrap"
      >
        <transition name="fade" mode="out-in">
          <div
            class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100 p-0"
          >
            <div class="p-0 content-wrapper w-100">
              <router-view :app="app" :userInfo="userInfo" :email="this" />
            </div>
          </div>
        </transition>

        <div
          class="p-0 content-wrapper w-100 d-flex email-login"
          v-if="!app.gmailAuth && !$route.params.email_type"
        >
          <a
            href="/gmail/login"
            class="md-button md-primary md-theme-default button-custom-regular connect-button d-flex align-center justify-content-center mx-auto"
          >
            Connect <LoadingButtonLoader :typeEnable="'simpleLoader'" v-bind:enable="app.loading && !app.gmailAuth" class="mr-1" />
          </a>
        </div>
      </div>
    </div>
    <!-- Main Email view end here -->
  </div>
</template>

<script>
import _ from "lodash";
import { HTTP } from "../../httpCommon";
import { VueEditor } from "vue2-editor";
import { required, email, minLength, maxLength } from "vuelidate/lib/validators";
import DocumentUploader from './reuseComponents/DocumentUploader';
import LoadingButtonLoader from "../../components/LoadingButtonLoader";

export default {
	props: ["app", "userInfo", "transaction_code", "allowEdit"],
	components: {
		VueEditor,
    DocumentUploader,
    LoadingButtonLoader,
	},
	data() {
		return {
      newMessageDialog: false,
      attachments: [],
      message: {},
      template: {},
      templates: [],
      chooseTemplate: null,
      showccbcc: false,
      showCc: false,
      showBcc: false,
      uploadFileDialog: false,
      uploadedAttachments: [],
		};
	},
  validations: {
    message: {
      to: {
          required,
          email
      },
      subject: {
          required,
          minLength: minLength(10),
          maxLength: maxLength(300)
      },
      body: {
          required,
          minLength: minLength(10),
          maxLength: maxLength(2000)
      }
    },
  },
	computed: {
    totalUnread() {
      return this.app.gmailTotalUnread;
    },
		transaction_id() {
			return this.$route.params.transaction_id ? this.$route.params.transaction_id : '';
		},
		email_type() {
			return this.$route.email_type;
		},
		route() {
			let route = {
				inbox: `/emails/INBOX`,
				draft: `/emails/DRAFT`,
				sent: `/emails/SENT`,
				trash: `/emails/TRASH`,
				emailTemplates: `/emails/templates`,
			}

			if(this.transaction_id != '') {
				route = {
					inbox: `/transactions/${this.transaction_id}/emails/INBOX`,
					draft: `/transactions/${this.transaction_id}/emails/DRAFT`,
					sent: `/transactions/${this.transaction_id}/emails/SENT`,
					trash: `/transactions/${this.transaction_id}/emails/TRASH`,
					emailTemplates: `/transactions/${this.transaction_id}/emails/templates`,
				}
			}
			return route;
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
	},
	created() {
		var code = this.$route.query.code;
    this.app.loading = true;
		if (code) {
			HTTP.get("api/gmail/callback", {
				params: { code: code },
			})
			.then((response) => {
				this.app.gmailAuth = true;
				this.app.mainLoader = false;
				if (this.transaction_id != '' && this.email_type != "INBOX") {
					this.$router.push({
						name: "SingleTransaction",
						params: { email_type: "INBOX" },
					});
				} else {
          if(this.email_type != "INBOX" && (this.email_type != "templates" || this.$route.name != 'EmailTemplates')) {
            this.$router.push({
              name: "EmailList",
              params: { email_type: "INBOX" },
            });
          }
				}

				this.fetchTemplates();
			})
			.catch((error) => {
				this.app.gmailAuth = false;
				this.app.mainLoader = false;
        this.app.loading = false;
			});
		} else {
			this.init();
			this.fetchTemplates();
		}
	},
  mounted() {
    this.$root.$on('refreshTemplates', arg => {
      this.fetchTemplates()
    });
  },
	methods: {
		init() {
      this.app.loading = true;
			HTTP.get("api/gmail/oauth")
			.then((response) => {
				if (response.data.Status) {
					this.app.gmailAuth = true;

					if (this.transaction_id != '' && this.email_type) {
            if(this.email_type != 'INBOX') {
              this.$router.push({
							name: "TransactionEmailList",
							params: { transaction_id: transaction_id, email_type: this.email_type },
						});
            }
					} else if ( !this.email_type && this.transaction_id == '') {
            if(this.$route.name != 'EmailList' && this.$route.name != 'EmailTemplates' && this.$route.name != 'EmailDetail') {
              this.$router.push({
                name: "EmailList",
                params: { email_type: "INBOX" },
              });
            }
					}
				} else {
					this.app.gmailAuth = false;
          this.app.loading = false;
          this.app.mainLoader = false;
				}
			})
			.catch((error) => {
				console.log(error);
				this.app.gmailAuth = false;
				this.app.loading = false;
        this.app.mainLoader = false;
			});
		},
		fetchTemplates() {
      this.app.loading = true;
			HTTP.get(`api/gmail/templates`)
			.then((response) => {
				this.templates = response.data.data;
        this.app.loading = false;
			})
			.catch((error) => {
				console.log(error);
        this.app.loading = false;
			});
		},
		sendEmail() {
      this.$v.message.$touch();
      if (this.$v.message.$error) return;
			this.app.loading = true;
			this.message.message_id = this.message.message_id?this.message.message_id:'';
			const data = {
				to: this.message.to,
				cc: this.message.cc,
				bcc: this.message.bcc,
				subject: this.message.subject,
				message: this.message.body,
				message_id: this.message.message_id,
        headers: {
          transaction_code: this.transaction_code,
          transaction_id: this.transaction_id
        },
				type: 'send'
			}
			HTTP.post(`api/gmail/send`, data)
			.then((response) => {
				this.$toasted.show(response.data.Message, {type: 'success'}).goAway(1500);
				this.message = {};
				this.newMessageDialog = false;
				this.app.loading = false;
			})
			.catch(error => {
				this.app.loading = false;
        this.$toasted.show('Email not sent', {type: 'error'}).goAway(1500);
				console.log('gmail send error');
				console.log(error.response);
			});
		},
		saveAsTemplate() {
      this.app.loading = true;
      this.template = this.message;
      this.template.name = this.message.subject+'_'+'Saved_from_compose';
			const data = {
				name: this.template.name,
				subject: this.template.subject,
				body: this.template.body,
			};

			HTTP.post(`api/gmail/templates`, data)
			.then(response => {
				this.close();
				this.template = {};
				this.fetchTemplates();
				this.$toasted.show(response.data.Message, {type: 'success'}).goAway(1500);
				this.app.loading = false;
			})
			.catch(error => {
				console.log(error);
			})
    },
		saveAsDraft: _.debounce(function(e) {
			this.attachFiles(e);
		}, 2100),
		attachFiles(e) {
			if(e.target.type == "file") {
				this.selectFiles(e);
			}
			this.app.loading = true;
			var data = new FormData();
			for ( var key in this.message ) {
				data.append(key, this.message[key]);
      }
			this.attachments.forEach(function(item, index) {
				data.append('attachments[]', item);
      });
      let message_id = this.message.message_id ? this.message.message_id : "";
      data.append("message_id", message_id);
      data.append("type", "draft");

      const config = { headers: { "Content-Type": "multipart/form-data" } };
      HTTP.post("api/gmail/attachments", data, config)
        .then((response) => {
          this.message.message_id = response.data.data.message_id;
          this.message.attachments = [];
          this.uploadedAttachments = this.attachments;
          this.app.loading = false;
          this.$toasted.show('Files attached',{type: 'success'}).goAway(1500);
        })
        .catch((error) => {
          if(error.response) {
            this.$toasted.show(error.response.data,{type: 'error'}).goAway(1500);
          }else {
            this.$toasted.show(error,{type: 'error'}).goAway(1500);
          }
          console.log(error);
          this.message.attachments = [];
          this.app.loading = false;
        });
    },
    selectFiles(e) {
      let files = e.target.files;
      if (!files.length) {
        return false;
      }
      for (let i = 0; i < files.length; i++) {
        this.attachments.push(files[i]);
      }
    },
    removeAttachment(name) {
      const data = {
        file: name,
        message_id: this.message.message_id
      }
      this.app.loading = true;
      HTTP.post("api/gmail/attachments/delete", data)
      .then((response) => {
        this.attachments = this.attachments.filter(item => {
          return item.name != response.data.data.file.path
        });
        this.uploadedAttachments = this.attachments;
        this.app.loading = false;
        this.$toasted.show(response.data.Message,{type: 'success'}).goAway(1500);
      })
      .catch((error) => {
        console.log(error);
        this.app.loading = false;
        this.$toasted.show("Draft not saved", { type: "error" }).goAway(1500);
      });
    },
    filesUploaded(data) {},
    toggleCc() {
      this.showccbcc = true;
      this.showCc = !this.showCc;
    },
    toggleBcc() {
      this.showccbcc = true;
      this.showBcc = !this.showBcc;
    },
    changeTemplate(value) {
      this.app.loading = true;
      let template = this.templates.filter(item => {
        return item.id == value;
      });
      this.message.body = template[0].body;
      this.app.loading = true;
      setTimeout(function(){ this.app.loading =  false; }.bind(this), 800);
    },
    close() {
      this.newMessageDialog = false;
      this.$v.message.$reset();
      this.message = {}
    },
    handleDialogClose(event) {
      this.uploadFileDialog = false;
    }
  },
};
</script>

<style lang="scss" scoped>
.email-login {
    margin-top: 25%;
}
.multi-error.vue-editor {
  display: block !important;
}
.content-wrapper {
  padding: 0 !important;
}
.sidebar-custom-width {
  width: 300px;
  max-width: 300px;
  flex: 0 0 300px;
  min-width: auto;
}
.inbox-content-width {
  width: calc(100% - 320px);
  min-width: calc(100% - 320px);
  max-width: calc(100% - 320px);
}
.inbox-list-text {
  font-size: 14px;
}
.close-btn {
  right: 20px;
  cursor: pointer;
  width: 25px;
  min-width: 20px;
  height: 30px;
}
hr {
  border: none;
  border-bottom: 1px solid #ebecef;
  display: block;
  width: 85%;
  margin: 20px auto 10px;
}
.md-list-item {
  .add-btn {
    flex-direction: initial;
    flex: inherit;
    align-items: center;
    color: #46e0a3 !important;
    .md-icon.plus-icon {
      margin: 1px 2px 0 0 !important;
      color: #46e0a3 !important;
    }
  }
}
.all-count {
  background: #ebecef !important;
}
.cancelled-label {
  color: #fff !important;
}

@media (max-width: 768px) {
  .sidebar-custom-width {
    width: 100%;
    max-width: 100%;
    -webkit-box-flex: 0;
    flex: 0 0 100%;
    min-width: auto;
  }
  .inbox-content-width {
    margin-top: 20px;
    width: calc(100%);
    min-width: calc(100%);
    max-width: calc(100%);
  }
}

// Upload file modal start here

.upload-drag-drop {
  border: 1px dashed #b2b2db;
  border-radius: 4px;
  text-align: center;
  min-height: 104px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
}

.form-config-upload {
  min-width: auto;
  height: 107px;
}
.pdf-icon {
  border: 1px solid #ebecef;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  max-width: 32px;
  flex: 0 0 32px;
  height: 32px;
  margin-right: 10px;
  img {
    width: 18px;
    height: 20px;
  }
}
.invoice-content {
  flex: 1;
  h4 {
    font-size: 16px !important;
    line-height: normal;
    margin-top: -5px !important;
  }
  p {
    font-size: 12px !important;
    margin: 0 !important;
    line-height: normal;
  }
  .delete-icon {
    display: flex;
  }
}
.invoice-row {
  border-bottom: none;
  padding-bottom: 8px;
  margin-bottom: 5px;
  background: #f6f6fb;
  padding: 11px 15px;
  border-radius: 7px;
}
.file-support-text {
  font-size: 12px !important;
}
.preloader {
  width: 25px;
  height: 25px;
}
</style>