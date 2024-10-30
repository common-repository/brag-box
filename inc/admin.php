<?php
/**
 *
 * Adds an brag box tab to the admin interface
 *
*/

add_action('admin_menu', 'spxo_bb_add_admin_tab');
function spxo_bb_add_admin_tab() {
	add_menu_page( 'Brag Box - All Posts', 'Brag Box Posts', 'manage_options', 'brag-box-posts', 'spxo_bb_all_posts', 'dashicons-admin-comments', 30);
	add_submenu_page ('brag-box-posts', 'Brag Box - All Posts', 'All Posts', 'manage_options', 'brag-box-posts', 'spxo_bb_all_posts');

	add_submenu_page ('brag-box-posts', 'Brag Box - Setup', 'Setup', 'manage_options', 'brag-box-setup', 'spxo_bb_setup');
}

add_action('admin_init', 'spxo_bb_admin_init');
function spxo_bb_admin_init() {
	wp_enqueue_style( 'spxo_bb_animate', plugins_url( '../css/spxo_animate.min.css', __FILE__ ));
	wp_enqueue_style( 'spxo_bb_style', plugins_url( '../css/spxo_bb_style.min.css', __FILE__ ));
}


function spxo_bb_all_posts() { ?>
	<?php

		// Get current page protocol
		$spxo_bb_protocol = isset( $_SERVER["HTTPS"] ) ? 'https://' : 'http://';

		// Output admin-ajax.php URL with same protocol as current page 
		$spxo_bb_protocol = admin_url( 'admin-ajax.php', $spxo_bb_protocol );

		global $wpdb; 
		
		$spxo_bb_all_brags = $wpdb->get_results("
			SELECT * FROM spxo_brag_box WHERE is_trashed = 0
		", OBJECT);
		$spxo_bb_all_trashed_brags = $wpdb->get_results("
			SELECT * FROM spxo_brag_box WHERE is_trashed = 1
		", OBJECT);

		foreach ($spxo_bb_all_brags as $spxo_bb_brag) {
			spxo_bb_add_meta_properties_to_object($spxo_bb_brag);
		}

		foreach ($spxo_bb_all_trashed_brags as $spxo_bb_trashed_brag) {
			spxo_bb_add_meta_properties_to_object($spxo_bb_trashed_brag);
		}
		
	?>

	<div class="Spxo-Bb-Admin-Interface wrap" id="Brag-App">
		<h2>View All Brag Box Posts</h2>
		<ul class="subsubsub">
			<li class="all">
				<a class="menu-item" v-bind:class="{ 'Spxo-Bb-Admin-Menu--Active': !isTrashPage}" @click="spxo_bb_to_all_brags_page">All
					<span class="count">({{ spxo_bb_brag_count }})</span>
				</a> |
			</li>	
			<li class="trash">
				<a class="menu-item" v-bind:class="{ 'Spxo-Bb-Admin-Menu--Active': isTrashPage}" @click="spxo_bb_to_trash_page">Trash
					<span class="count">({{ spxo_bb_trashed_brag_count }})</span>
				</a>
			</li>
			<li class="Spxo-Bb-Search">
				<div class="Spxo-Form-Group" v-show="!isTrashPage">
					<label class="Spxo-Sr-Only" for="All-Brags-Search">Search Brags</label>
					<input id="All-Brags-Search" class="Spxo-Form-Control" placeholder="Search Brags" type="text" v-model="allQuery">
				</div>
				<div class="Spxo-Form-Group" v-show="isTrashPage">
					<label class="Spxo-Sr-Only" for="Trashed-Brags-Search">Search Brags</label>
					<input id="Trashed-Brags-Search" class="Spxo-Form-Control" placeholder="Search Brags" type="text" v-model="trashQuery">
				</div>
			</li>
		</ul>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Brag</th>
					<th>Author</th>
					<th>Bragged About</th>
					<th>Date</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Brag</th>
					<th>Author</th>
					<th>Bragged About</th>
					<th>Date</th>
				</tr>
			</tfoot>
			<tbody>
				<!-- Foreach looping through all brags gonna go hur -->
					<tr v-for="brag in brags | filterBy allQuery" v-if="!isTrashPage" transition="Spxo-Bb-Staggered" stagger="100">
						<td>
							<strong>
								{{ brag.testimonial }}
							</strong>
							<div class="trash text-info"  @click="spxo_bb_move_brag_to_trash($index)">
								<a>
									<i class="fa fa-trash"></i>
									<span>Move to Trash</span>
								</a>
							</div>
						</td>
						<td>{{ brag.postingUser }}</td>
						<td>{{ brag.person_recognized }}</td>
						<td class="date column-date">
							Created
							{{ brag.prettyDate }}
						</td>
					</tr>
					<tr v-show="brags.length == 0 && !isTrashPage">
						<td>No brags found</td>
						<td></td>
						<td></td>
						<td></td>
					</tr>


					<tr v-for="brag in trashedBrags | filterBy trashQuery" v-if="isTrashPage" transition="Spxo-Bb-Staggered" stagger="100">
						<td>
							<strong>
								{{ brag.testimonial }}
							</strong>

							<div class="Options" >
								<a class="text-info" @click="spxo_bb_restore_brag($index)">Restore</a> |
								<a class="Spxo-Text-Danger" @click="spxo_bb_delete_brag($index)">Delete Permanently</a>
							</div>
						</td>
						<td>{{ brag.postingUser }}</td>
						<td>{{ brag.person_recognized }}</td>
						<td class="date column-date">
							Created
							{{ brag.prettyDate }}
						</td>
					</tr>
					<tr v-show="trashedBrags.length == 0 && isTrashPage">
						<td>No brags found</td>
						<td></td>
						<td></td>
						<td></td>
					</tr>

			</tbody>
		</table>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/1.0.16/vue.min.js"></script>
	<script type="text/javascript">
	var preloaded = {
	    'brags' : <?php echo json_encode($spxo_bb_all_brags); ?>,
	    'trashedBrags' : <?php echo json_encode($spxo_bb_all_trashed_brags); ?>,
	    'userID' : <?php echo json_encode(wp_get_current_user()->data->ID); ?>,
	}

	new Vue({
		el: "#Brag-App",
		data: {
			brags: [],
			trashedBrags: [],
			isTrashPage: false,
			allQuery: "",
			trashQuery: "",
			userID: preloaded.userID,
		},
		computed: {
			spxo_bb_brag_count: function() {
				return this.brags.length
			},
			spxo_bb_trashed_brag_count: function() {
				return this.trashedBrags.length
			}
		},
		ready: function() {
			this.brags = preloaded.brags;
			this.trashedBrags = preloaded.trashedBrags;
		},
		methods: {
			spxo_bb_move_brag_to_trash: function(index) {
					// Send ajax call that takes this brags id and updates its in trash record to true
					jQuery.ajax({
						url: "<?php echo $spxo_bb_protocol; ?>",
						type: "POST",
						data: {
							action: 'spxo_bb_move_brag_to_trash',
							bragID: this.brags[index].id,
							userID: this.userID
						},
						success: function(response) {

						}
					});
					this.trashedBrags.unshift(this.brags[index]);
					this.brags.splice(index,1);
			},
			spxo_bb_restore_brag: function(index) {
					// Send ajax call that takes this brags id and updates its in trash record to true
					jQuery.ajax({
						url: "<?php echo $spxo_bb_protocol; ?>",
						type: "POST",
						data: {
							action: 'spxo_bb_restore_brag',
							bragID: this.trashedBrags[index].id
						},
						success: function(response) {
						}
					});
					this.brags.unshift(this.trashedBrags[index]);
					this.trashedBrags.splice(index,1);
			},
			spxo_bb_delete_brag: function(index) {
				if(confirm("This brag will be deleted permanently. Are you sure you wish to continue?")) {
					jQuery.ajax({
						url: "<?php echo $spxo_bb_protocol; ?>",
						type: "POST",
						data: {
							action: 'spxo_bb_delete_brag',
							bragID: this.trashedBrags[index].id
						},
						success: function(response) {
							// console.log(response);
							this.trashedBrags.splice(index,1);
						}.bind(this)
					});
				}
			},
			spxo_bb_to_all_brags_page: function() {
				this.allQuery = ""
				this.isTrashPage = false;
			},
			spxo_bb_to_trash_page: function() {
				this.trashQuery = ""
				this.isTrashPage = true;
			}
		}
	});
	
</script>

<?php } 

function spxo_bb_setup() { ?>
	<div class="wrap" id="Brag-App">
		<h2>Setup</h2>
		<table class="form-table">
			<tbody>
				<tr>
					<th>Shortcode:</th>
					<td>
						<p><strong>[brag-box]</strong></p>
						<p class="description">Place this shortcode into the content editor or widget area of your theme. Your brag box will show wherever it is placed.</p>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
<?php }