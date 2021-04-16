<template>
  <div>
    <md-dialog-confirm
      :md-active.sync="confirmBox"
      md-title="Are you sure you want to delete?"
      md-content=""
      md-confirm-text="Yes"
      md-cancel-text="No"
      @md-cancel="onCancel"
      @md-confirm="trash" />
       <!-- New message dialog start here -->
    <md-dialog
      :md-active.sync="newMessageDialog"
      class="modal-large new-message-modal"
    >
      <md-dialog-title
        class="modal-title d-block py-3 px-4 bg-light position-relative"
      >
        <h4 class="my-0 md-headline text-left text-primary">{{ selectedItem.type }}</h4>
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
            <div class="md-layout-item md-small-size-100 md-size-100" v-if="sendType != 'reply'">
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
            <div class="md-layout-item md-small-size-100 md-size-45" v-if="sendType != 'reply'">
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
    <div
      class="md-layout-item md-medium-size-65 md-xsmall-size-100 md-size-100 email-listing"
    >
      <md-card
        class="md-card-plain email-card md-card-custom custom-shadow radius-10 mt-0"
      >
        <md-card-content
          class="px-0 py-0 height-auto radius-10"
          v-if="messages[0]"
        >
          <div class="email-description-header pt-3 pr-4 d-flex align-center">
            <div class="email-options" @click="$router.go(-1)">
              <md-icon class="print-icon ml-0 mr-3 back-arrow"
                >arrow_back</md-icon
              >
            </div>
            <h3 class="text-primary mt-0 mb-0 mr-3">
              {{ utf8_decode(messages[0].subject) }}
            </h3>
            <md-chip
              class="md-primary"
              md-deletable
              v-for="label in messages[0].labels"
              :key="label"
              >{{ label }}</md-chip
            >
            <div class="email-options ml-auto">
              <md-icon class="print-icon mr-3">local_printshop</md-icon>
              <md-icon class="print-icon">launch</md-icon>
            </div>
          </div>
          <div
            class="email-thread"
            v-for="message in messages"
            :key="message.id"
            id="section-to-print"
          >
            <div
              class="email-description-subheader pt-3 pl-3 pr-4 d-flex align-center flex-wrap"
            >
              <div class="md-avatar ml-0 mr-3">
                <img src="/img/profile_mask2.png" alt="People" />
              </div>
              <div
                class="email-description-subheader-content d-flex align-center flex-wrap"
              >
                <span class="text-primary font-weight-semi-bold mr-2">
                  {{ message.from_with_email.name }}
                </span>
                <span v-if="message.from_with_email.email">
                  &lt;{{ message.from_with_email.email }}&gt;
                </span>
                <div
                  class="email-secription-options ml-auto d-flex align-center"
                >
                  <span class="text-secondary mr-3">
                    {{ message.date_time }} ({{ message.time_passed }})
                  </span>
                  <div class="email-options ml-auto">
                    <md-icon class="print-icon mr-3">star_border</md-icon>
                    <span @click="openReply({item: message, recipient: 'one'})"><md-icon class="print-icon mr-3">undo</md-icon></span>
                    <md-menu md-size="medium" md-align-trigger>
                      <md-icon md-menu-trigger class="print-icon"
                        >more_vert</md-icon
                      >
                      <md-menu-content class="accordion-dropdown-content">
                        <md-menu-item @click="openReply({item: message, recipient: 'one'})" v-if="permission">
                          <md-icon class="accordion-icon">reply</md-icon>
                          Reply
                        </md-menu-item>
                        <md-menu-item @click="openReply({item: message, recipient: 'all'})" v-if="permission">
                          <md-icon class="accordion-icon">reply_all</md-icon>
                          Reply to all
                        </md-menu-item>
                        <md-menu-item @click="openForward(message)" v-if="permission">
                          <md-icon class="accordion-icon"
                            >forward_to_inbox</md-icon
                          >
                          Forward
                        </md-menu-item>
                        <md-menu-item  @click="openPrint">
                          <md-icon class="accordion-icon">print</md-icon>
                          Print
                        </md-menu-item>
                        <md-menu-item @click="openDelete(message)" v-if="permission && permissionDelete">
                          <md-icon class="accordion-icon">delete</md-icon>
                          Delete this message
                        </md-menu-item>
                      </md-menu-content>
                    </md-menu>
                  </div>
                </div>
                <div class="email-sender-info d-flex align-center">
                  <div class="text-secondary">
                    to me
                    <md-menu md-size="medium" md-align-trigger>
                      <md-icon md-menu-trigger class="down-arrow-icon"
                        >arrow_drop_down</md-icon
                      >
                      <md-menu-content class="email-dropdown-content">
                        <table cellpadding="0">
                          <tbody>
                            <tr>
                              <td colspan="2" tabindex="0">
                                <span class="sender-header">from:</span>
                              </td>
                              <td colspan="2" tabindex="0">
                                <span>
                                  <span tabindex="-1">
                                    <strong>{{
                                      message.from_with_email.name
                                    }}</strong>
                                    <span
                                    v-if="message.from_with_email.email"
                                      >&lt;{{
                                        message.from_with_email.email
                                      }}&gt;</span
                                    >
                                  </span>
                                </span>
                              </td>
                            </tr>
                            <tr>
                              <td colspan="2" tabindex="0">
                                <span class="sender-header">reply-to:</span>
                              </td>
                              <td colspan="2" tabindex="0">
                                <span>
                                  {{ message.reply_to.name }}
                                  <span
                                    v-if="message.reply_to.email">
                                    &lt;{{
                                      message.reply_to.email
                                    }}&gt;</span
                                  >
                                  <br />
                                </span>
                              </td>
                            </tr>
                            <tr>
                              <td colspan="2" tabindex="0">
                                <span class="sender-header">to:</span>
                              </td>
                              <td colspan="2" tabindex="0">
                                <span v-for="to in message.to" :key="to.email">
                                  <span>{{ to.email }}</span
                                  ><span
                                    v-if="
                                      message.to.length <= message.to.length - 1
                                    "
                                    >,</span
                                  >
                                  <br />
                                </span>
                              </td>
                            </tr>
                            <tr>
                              <td colspan="2" tabindex="0">
                                <span class="sender-header">cc:</span>
                              </td>
                              <td colspan="2" tabindex="0">
                                <span v-for="cc in message.cc" :key="cc.email">
                                  <span>{{ cc.email }}</span
                                  ><span
                                    v-if="
                                      message.cc.length <= message.cc.length - 1
                                    "
                                    >,</span
                                  >
                                  <br />
                                </span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </md-menu-content>
                    </md-menu>
                  </div>
                </div>
              </div>
            </div>
            <div class="email-body">
              <div class="email-content-body">
                <div
                  class="description text-primary"
                  v-html="utf8_decode(message.body)"
                ></div>
              </div>
              <div class="email-attachments" v-if="message.has_attachments">
                <ul>
                  <li
                    v-for="attachment in message.attachments"
                    :key="attachment.name"
                  >
                    <img
                      :src="attachment.path"
                      :alt="attachment.name"
                      class="thumbnail"
                    />
                    <div class="hover-content d-flex flex-wrap">
                      <md-icon class="image-icons">image</md-icon>
                      <div class="hover-image-content">
                        <p>
                          Design to Development Workflow - MaterialUI, React.
                        </p>
                        <p class="shared-text">Shared in Drive</p>
                        <md-icon class="download-icons">download</md-icon>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </md-card-content>
      </md-card>
    </div>
  </div>
</template>

<script>
import { HTTP } from "../../httpCommon";
import { VueEditor } from "vue2-editor";
import { required, email, minLength, maxLength } from "vuelidate/lib/validators";

export default {
  props: ["app", "email", "transaction_code", "allowEdit"],
  components: {
    VueEditor,
  },
  data() {
    return {
      newMessageDialog: false,
      message: {},
      messages: [],
      selectedItem: {type: 'Reply'},
      confirmBox: false,
      showccbcc: false,
      showCc: false,
      showBcc: false,
      uploadedAttachments: [],
      sendType: 'send',
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
    transaction_id() {
			return this.$route.params.transaction_id ? this.$route.params.transaction_id : '';
		},
    type() {
      return this.$route.params.email_type;
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
      this.app.mainLoader = true;
      var currentFolder = this.$route.params.email_type.toUpperCase();
      var emailId = this.$route.params.email_id;
      HTTP.get(`api/gmail/${currentFolder}/${emailId}`)
        .then((response) => {
          this.app.mainLoader = false;
          this.messages = response.data.data.messages;
        })
        .catch((error) => {
          this.app.mainLoader = false;
          console.error(error.message);
        });
    },
    sendEmail() {
      this.$v.message.$touch();
      if (this.$v.message.$error) return;
			this.app.loading = true;

			const data = {
				to: this.message.to,
				cc: this.message.cc,
				bcc: this.message.bcc,
				subject: this.message.subject,
				body: this.message.body,
				message_id: this.message.message_id,
				thread_id: this.message.thread_id,
        type: this.sendType,
        headers: {
          transaction_code: this.transaction_code,
          transaction_id: this.transaction_id
        },
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
    openReply(data) {
      this.sendType = 'reply';
      let item = data.item;
      this.message.subject = item.subject;
      this.message.message_id = item.id;
      this.message.thread_id = item.id;

      if(data.recipient == 'one') {
        this.message.to = item.reply_to.email ? item.reply_to.email : item.from;
      } else {
        let reply_to = item.reply_to.email;
        let to_emails = item.to.map(user => { return user.email; });
        let cc_emails = item.cc.map(user => { return user.email; });
        let bcc_emails = item.bcc.map(user => { return user.email; });
        let to = to_emails.filter(user => { return user.email != reply_to; });
        this.message.to = to;
        if(cc_emails.length) {
          this.message.cc = cc_emails;
          this.showccbcc = true;
          this.showCc = true;
        }
        if(bcc_emails.length) {
          this.message.bcc = bcc_emails;
          this.showccbcc = true;
          this.showBcc = true;
        }
      }
      this.newMessageDialog = true;
    },
    openForward(item) {
      this.sendType = 'forward';
      this.message.message_id = item.id;
      this.message.thread_id = item.id;
      this.message.subject = item.subject;
      var str = item.body.replace(/<div/g, "<p");
      this.message.body = str.replace(/<\/div>/g,"</p>");
      this.newMessageDialog = true;
    },
    openPrint() {
      window.print();
    },
    openDelete(message) {
      this.confirmBox = true;
      this.selectedItem = message;
    },
    trash() {
      let item = this.selectedItem;
			HTTP.delete(`api/gmail/${this.type}/${item.id}/delete`)
			.then(response => {
				this.$toasted.show(response.data.data.Message, {
					type: 'success'
				}).goAway(1500);
			})
			.catch(error => {
				console.log(error);
			})
		},
    onCancel() {
      this.selectedItem = {};
    },
    onSelectOne() {},
    toggleCc() {
      this.showccbcc = true;
      this.showCc = !this.showCc;
    },
    toggleBcc() {
      this.showccbcc = true;
      this.showBcc = !this.showBcc;
    },
    close() {
      this.newMessageDialog = false;
      this.message = false;
    },
  },
};
</script>

<style lang="scss" scoped>
.email-options {
  .print-icon {
    width: 20px;
    height: 20px;
    min-width: 20px;
    font-size: 22px !important;
    cursor: pointer;
    &:after {
      content: "";
      background-color: rgba(32, 33, 36, 0.059);
      border: none;
      box-shadow: none;
      opacity: 0;
      transform: scale(0);
      width: 40px;
      height: 40px;
      position: absolute;
      display: flex;
      border-radius: 100%;
      transition: all 0.2s;
    }
    &:hover:after {
      transform: scale(1);
      opacity: 1;
    }
  }
}
.email-description-header {
  padding-left: 27px !important;
  .print-icon {
    &.back-arrow {
      margin-right: 24px !important;
    }
  }
}
.email-description-subheader-content {
  width: calc(100% - 56px);
}
.email-sender-info {
  flex: 0 0 100%;
  max-width: 100%;
  .down-arrow-icon {
    cursor: pointer;
  }
}
.email-body {
  padding: 20px;
  .email-content-body {
    padding: 20px;
    background-color: #f5f5f5 !important;
  }
}
img.thumbnail {
  max-width: 200px;
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
.multi-error.vue-editor {
  display: block !important;
}
</style>