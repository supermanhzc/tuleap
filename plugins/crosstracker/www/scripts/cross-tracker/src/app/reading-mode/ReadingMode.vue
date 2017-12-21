(<template>
    <div class="dashboard-widget-content-cross-tracker-reading-mode">
        <div class="cross-tracker-reading-mode-fields"
            v-bind:class="{'reading-mode-disabled': ! is_report_loaded, 'disabled' : is_user_anonymous}"
            v-on:click="switchToWritingMode"
        >
            <tracker-list-reading-mode
                v-bind:reading-cross-tracker-report="readingCrossTrackerReport"
            ></tracker-list-reading-mode>
        </div>
        <div class="dashboard-widget-content-cross-tracker-reading-mode-actions cross-tracker-hide">
            <button class="tlp-button-primary tlp-button-small tlp-button-outline dashboard-widget-content-cross-tracker-reading-mode-actions-cancel">{{ cancel }}</button>
            <button class="tlp-button-primary tlp-button-small dashboard-widget-content-cross-tracker-reading-mode-actions-save">
                <span class="cross-tracker-loader"><i class="tlp-button-icon fa fa-spinner fa-spin"></i></span>
                {{ save_report }}
            </button>
        </div>

        <artifact-table-renderer></artifact-table-renderer>
    </div>
</template>)
(<script>
    import ArtifactTableRenderer  from '../ArtifactTableRenderer.vue';
    import TrackerListReadingMode from './TrackerListReadingMode.vue';
    import { gettext_provider }   from '../gettext-provider.js';
    import { isAnonymous }        from '../user-service.js';

    export default {
        components: { ArtifactTableRenderer, TrackerListReadingMode } ,
        props: [
            'backendCrossTrackerReport',
            'readingCrossTrackerReport',
            'writingCrossTrackerReport',
            'reportMode'
        ],
        computed: {
            save_report:() => gettext_provider.gettext("Save report"),
            cancel:()      => gettext_provider.gettext("Cancel"),
            is_report_loaded() {
                return this.backendCrossTrackerReport.loaded;
            },
            is_user_anonymous() {
                return isAnonymous();
            },
        },
        methods: {
            switchToWritingMode() {
                if (this.is_user_anonymous) {
                    return;
                }

                this.writingCrossTrackerReport.duplicateFromReport(this.readingCrossTrackerReport);
                this.reportMode.switchToWritingMode();
            }
        },
    };
</script>)