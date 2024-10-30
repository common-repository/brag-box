<?php

add_action( 'init', 'spxo_bb_register_shortcodes');
function spxo_bb_register_shortcodes() {
	add_shortcode( 'brag-box', 'spxo_bb_front_end' );
}


add_action( 'wp_enqueue_scripts', 'spxo_bb_front_end_init');
function spxo_bb_front_end_init() {
		wp_enqueue_style( 'spxo_bb_font-awesome', plugins_url( '../css/spxo_font-awesome.min.css', __FILE__ ));
		wp_enqueue_style( 'spxo_bb_style', plugins_url( '../css/spxo_bb_style.min.css', __FILE__ ));
		wp_enqueue_script( 'spxo_vue', plugins_url( '../js/spxo_vue.min.js', __FILE__ ), null, '1.0.22' , false );
}


function spxo_bb_front_end() { 

		// Get current page protocol
		$spxo_bb_protocol = isset( $_SERVER["HTTPS"] ) ? 'https://' : 'http://';

		// Output admin-ajax.php URL with same protocol as current page 
		$spxo_bb_ajax_url = admin_url( 'admin-ajax.php', $spxo_bb_protocol );

		global $wpdb;
		$spxo_bb_current_user = wp_get_current_user();

		$spxo_bb_brag_query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM spxo_brag_box WHERE is_trashed = %s
					ORDER BY created DESC",
					'0'
			)
		);

		foreach ($spxo_bb_brag_query as $spxo_bb_brag) {
			spxo_bb_add_meta_properties_to_object($spxo_bb_brag);
		}

	?>

	<section class="Spxo-Bb-Container" id="Spxo-Bb-Front-End">
		<div class="Spxo-Bb">
			<div class="Spxo-Bb-Module-Title">Brag Box</div>
			
			<div class="Spxo-Bb-Wrapper">

				<!-- Brags Loop -->
				<div class="Spxo-Bb-Brag-Info" v-for="brag in brags" v-show="$index == currentIndex" transition="Spxo-Bb-Fade">

					<div class="Spxo-Bb-Brag-Info__Container">
						<div class="Spxo-Bb-Brag-Info__Delete-Button" @click="spxo_bb_move_brag_to_trash($index)" v-if="brag.user_id == userID">
							<i class="fa fa-trash fa-lg"></i>
						</div>
						<p class="Spxo-Bb-Brag-Info__Testimonial">
							{{ brag.testimonial }}
						</p>
						<p class="Spxo-Bb-Brag-Info__Bragged-About">
							Bragged about: {{ brag.person_recognized }}
						</p>
						<p class="Spxo-Bb-Brag-Info__Bragger">
							Bragger: {{ brag.currentUsersName }}
						</p>
					</div>

				</div>	

			<div class="Spxo-Bb-Up-and-Down-Arrows" v-show="brags.length > 1">
				<span class="Spxo-Bb-Up-and-Down-Arrows__Up" @click="spxo_bb_cycle_up"><i class="fa fa-caret-up fa-lg"></i></span>
				<span class="Spxo-Bb-Up-and-Down-Arrows__Down" @click="spxo_bb_cycle_down"><i class="fa fa-caret-down fa-lg"></i></span>
			</div>

			<form @submit.prevent="spxo_bb_add_brag" v-if="userID">

				<!-- Input -->
				<div class="Spxo-Form-Group">
					<div class="input-group">
						<div class="input-group-addon">
							<i class="fa fa-comment Spxo-Bb-Fa-Icon"></i>
						</div>
						<input type="text" placeholder="Write your brag here..." name="testimonial" v-model="brag" class="Spxo-Form-Control Spxo-Bb-Input" maxlength="300">
					</div>
				</div>

				<!-- Input -->
				<div class="Spxo-Form-Group">
					<div class="input-group">
						<div class="input-group-addon">
							<i class="fa fa-user Spxo-Bb-Fa-Icon"></i>
						</div>
						<input type="text" placeholder="Who do you want to brag about?" name="employeeRecognized" v-model="person_recognized" class="Spxo-Form-Control Spxo-Bb-Input" maxlength="30">
					</div>
				</div>

				<!-- Validation Alerts -->
				<div class="Spxo-Alert Spxo-Alert-Warning" v-show="spxo_bb_error_message">{{ spxo_bb_error_message }}</div>
				<div class="Spxo-Alert Spxo-Alert-Danger" v-show="showSubmissionError">{{ spxo_bb_submission_error_message }}</div>

				<div class="Spxo-Bb-Submit">
					<input class="Spxo-Bb-Submit__Button" type="submit" name="submit" value="Submit Brag">
				</div>
			</form>

		</div>
	</section>

	<script type="text/javascript">
	var preloaded = {
	    'brags' : <?php echo json_encode($spxo_bb_brag_query); ?>,
	    'userID' : <?php echo json_encode($spxo_bb_current_user->ID); ?>,
	}

	new Vue({
		el: "#Spxo-Bb-Front-End",
		data: {
			brag: "",
			person_recognized: "",
			brags: [],
			userID: preloaded.userID,
			currentIndex: 0,
			showSubmissionError: false
		},
		ready: function() {
			this.brags = preloaded.brags;
		},
		computed: {
			spxo_bb_error_message: function() {
				var bragError = "Brag cannot be greater than 300 characters in length.";
				var employeeError = "Employee name cannot be greater than 30 characters in length.";
				var fullError = bragError + " " + employeeError;

				if (this.brag.length >= 300 && this.person_recognized.length >= 30) {
					return fullError;
				} else if(this.brag.length >= 300) {
					return bragError;
				} else if(this.person_recognized.length >= 30) {
					return employeeError;
				} else {
					return null;
				};
			},
			spxo_bb_submission_error_message: function() {
				if (this.brag.length <= 5 && this.person_recognized.length <= 0) {
					return 'Brag must be greater than 5 characters. \n "Who do you want to brag about?" field cannot be empty.';
				} else if (this.brag.length <= 5) {
					return "Brag must be greater than 5 characters.";
				} else if (this.person_recognized.length <= 0) {
					return "'Who do you want to brag about?' field cannot be empty.";
				} else {
					this.showSubmissionError = false;
					return null;
				}
			}
		},
		methods: {
			spxo_bb_add_brag: function() {

				if (this.brag.length <= 5 || this.person_recognized.length <= 0) {
					this.showSubmissionError = true;
				} else {
					// Ajax call returns JSON rather than full HTML string
					jQuery.ajax({
						url: "<?php echo $spxo_bb_ajax_url; ?>",
						type: "POST",
						dataType: "json",
						data: {
							action: "spxo_bb_add_brag",
							brag: this.brag,
							person_recognized: this.person_recognized,
						},
						success: function(response) {

							// Remove error message if successful
							this.showSubmissionError = false;

							// Add brag to beginning of array
							this.brags.unshift(response[0]);

							// Move to beginning of array to see newest brag
							this.currentIndex = 0;

							// Remove text from input fields
							this.brag = "";
							this.person_recognized = "";
						}.bind(this)
					});
				}

			},
			spxo_bb_move_brag_to_trash: function(index) {
				if(confirm("You posted this brag. Are you sure you wish to delete it?")) {
					// Send ajax call that takes this brags id and updates its in trash record to true
					jQuery.ajax({
						url: "<?php echo $spxo_bb_ajax_url; ?>",
						type: "POST",
						data: {
							action: 'spxo_bb_move_brag_to_trash',
							bragID: this.brags[index].id,
							userID: this.userID
						},
						success: function(response) {
						}
					});
					this.brags.splice(index,1);
					this.currentIndex = 0;
				}
			},
			spxo_bb_cycle_down: function() {
				// Ensure user cannot cycle downwards past the amount of brags in brag list
				if (this.currentIndex < this.brags.length - 1) {
					this.currentIndex += 1;	
				};
			},
			spxo_bb_cycle_up: function() {
				// Ensure user cannot cycle upwards below 0
				if (this.currentIndex > 0) {
					this.currentIndex -= 1;	
				}
			}
		}
	});
	</script>

<?php } ?>