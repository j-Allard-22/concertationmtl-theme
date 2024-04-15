<?php

// late child theme style loading
add_action('wp_enqueue_scripts', 'tt_child_enqueue_parent_styles');

function tt_child_enqueue_parent_styles()
{
	wp_enqueue_style('select2-style', get_stylesheet_directory_uri() . '/css/select2.min.css');
	wp_enqueue_script('select2-js', get_stylesheet_directory_uri() . '/js/select2.min.js', array());

	wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
	wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/script.js', array());
	// Localize the script with new data
	$script_data_array = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	);
	wp_localize_script( 'custom-js', 'custom_js_data', $script_data_array );
}

add_theme_support('post-thumbnails');

add_image_size('pdf', 225, 277);
add_image_size('photo_corps', 650, 650);

// Register the useful image size for use in Add Media modal
add_filter('image_size_names_choose', 'your_custom_sizes');
function your_custom_sizes($sizes)
{
	return array_merge($sizes, array(
		'pdf' => __('Aperçu pdf - 225 x 277'),
		'photo_corps' => __('Photo corps de texte - 650 largeur'),
	));
}

// exclure catégorie parcours inspirants de la section nouvelles
function exclude_category_home( $query ) {
	if ( $query->is_home ) {
	$query->set( 'cat', '-250,-290' );
	}
	return $query;
}
	  
add_filter( 'pre_get_posts', 'exclude_category_home' );

// Traduire "Read more" sur la page d'accueil
add_action( 'wp_footer', 'ava_custom_script' );
function ava_custom_script() {
	?>
	<script type="text/javascript">
	(function($) {
		function a() {
			$(".wp-block-latest-posts__post-excerpt a").empty().text('Lire la suite2');
		}

		a();
	})(jQuery);
	</script>
	<?php
}

// Traduire "Reset" sur la page des bios de l'équipe ??? à faire charger seulement sur la page des bios de l'équipe
add_action( 'wp_footer', 'script_bio' );
function script_bio() {
	?>
	<script type="text/javascript">
	(function($) {
		function b() {
			$(".gv-search-box-submit a").empty().text('Effacer');
		}

		b();
	})(jQuery);
	</script>
	<?php
}

/**
 * Changer le upload folder des pdf (voir extension redirection pour la redirection ensuite pour permettre des url de type concertationmontreal.ca/NOM_DU.pdf
 *
 * Change upload directory for PDF files
 * Only works in WordPress 3.3+
 * Source: https://wordpress.stackexchange.com/questions/47415/change-upload-directory-for-pdf-files
 */

add_filter('wp_handle_upload_prefilter', 'wpse47415_pre_upload');
add_filter('wp_handle_upload', 'wpse47415_post_upload');

function wpse47415_pre_upload($file)
{
	add_filter('upload_dir', 'wpse47415_custom_upload_dir');
	return $file;
}

function wpse47415_post_upload($fileinfo)
{
	remove_filter('upload_dir', 'wpse47415_custom_upload_dir');
	return $fileinfo;
}

function wpse47415_custom_upload_dir($path)
{
	if ( isset( $_POST['name'] ) ) {
		$extension = substr( strrchr( sanitize_input( $_POST['name'] ), '.' ), 1 );
	} else {
		$extension = '';
	}
	if (!empty($path['error']) ||  $extension != 'pdf') {
		return $path;
	} //error or other filetype; do nothing.
	$customdir = '/pdf';
	$path['path']    = str_replace($path['subdir'], '', $path['path']); //remove default subdir (year/month)
	$path['url']     = str_replace($path['subdir'], '', $path['url']);
	$path['subdir']  = $customdir;
	$path['path']   .= $customdir;
	$path['url']    .= $customdir;
	return $path;
}


/**
 * Changer le logo et le style de la page login
 */
function wpb_login_logo()
{ 
	$upload_dir = wp_upload_dir();
	?>
	<style type="text/css">
		#login h1 a,
		.login h1 a {
			background-image: url('<?php echo $upload_dir['baseurl']; ?>/2019/11/LOGO-CMTL-120x90-1.png');
			height: 90px;
			width: 120px;
			background-size: 120px 90px;
			background-repeat: no-repeat;
			padding-bottom: 10px;
		}

		body.login {
			background: white;
		}

		#login p.message {
			border-left-color: #ed174b;
		}

		#login input.button-primary:active,
		#login input.button-primary {
			background: #ed174b;
			border-color: #ed174b;
		}

		#login #user_login:focus {
			border-color: #ed174b;
			box-shadow: 0 0 0 1px #ed174b;
		}
	</style>
	<?php }
add_action('login_enqueue_scripts', 'wpb_login_logo');

/**
 * Limit How Many Checkboxes Can Be Checked
 * http://gravitywiz.com/2012/06/11/limiting-how-many-checkboxes-can-be-checked/
 */

class GFLimitCheckboxes
{

	private $form_id;
	private $field_limits;
	private $output_script;

	function __construct($form_id, $field_limits)
	{

		$this->form_id = $form_id;
		$this->field_limits = $this->set_field_limits($field_limits);

		add_filter("gform_pre_render_$form_id", array(&$this, 'pre_render'));
		add_filter("gform_validation_$form_id", array(&$this, 'validate'));
	}

	function pre_render($form)
	{

		$script = '';
		$output_script = false;

		foreach ($form['fields'] as $field) {

			$field_id = $field['id'];
			$field_limits = $this->get_field_limits($field['id']);

			if (
				!$field_limits                                          // if field limits not provided for this field
				|| RGFormsModel::get_input_type($field) != 'checkbox'   // or if this field is not a checkbox
				|| !isset($field_limits['max'])        // or if 'max' is not set for this field
			)
				continue;

			$output_script = true;
			$max = $field_limits['max'];
			$selectors = array();

			foreach ($field_limits['field'] as $checkbox_field) {
				$selectors[] = "#field_{$form['id']}_{$checkbox_field} .gfield_checkbox input:checkbox";
			}

			$script .= "jQuery(\"" . implode(', ', $selectors) . "\").checkboxLimit({$max});";
		}

		GFFormDisplay::add_init_script($form['id'], 'limit_checkboxes', GFFormDisplay::ON_PAGE_RENDER, $script);

		if ($output_script) :
	?>

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$.fn.checkboxLimit = function(n) {

						var checkboxes = this;

						this.toggleDisable = function() {

							// if we have reached or exceeded the limit, disable all other checkboxes
							if (this.filter(':checked').length >= n) {
								var unchecked = this.not(':checked');
								unchecked.prop('disabled', true);
							}
							// if we are below the limit, make sure all checkboxes are available
							else {
								this.prop('disabled', false);
							}

						}

						// when form is rendered, toggle disable
						checkboxes.bind('gform_post_render', checkboxes.toggleDisable());

						// when checkbox is clicked, toggle disable
						checkboxes.click(function(event) {

							checkboxes.toggleDisable();

							// if we are equal to or below the limit, the field should be checked
							return checkboxes.filter(':checked').length <= n;
						});

					}
				});
			</script>

			<?php
		endif;

		return $form;
	}

	function validate($validation_result)
	{

		$form = $validation_result['form'];
		$checkbox_counts = array();

		// loop through and get counts on all checkbox fields (just to keep things simple)
		foreach ($form['fields'] as $field) {

			if (RGFormsModel::get_input_type($field) != 'checkbox')
				continue;

			$field_id = $field['id'];
			$count = 0;

			foreach ($_POST as $key => $value) {
				if (strpos($key, "input_{$field['id']}_") !== false)
					$count++;
			}

			$checkbox_counts[$field_id] = $count;
		}

		// loop through again and actually validate
		foreach ($form['fields'] as &$field) {

			if (!$this->should_field_be_validated($form, $field))
				continue;

			$field_id = $field['id'];
			$field_limits = $this->get_field_limits($field_id);

			$min = isset($field_limits['min']) ? $field_limits['min'] : false;
			$max = isset($field_limits['max']) ? $field_limits['max'] : false;

			$count = 0;
			foreach ($field_limits['field'] as $checkbox_field) {
				$count += rgar($checkbox_counts, $checkbox_field);
			}

			if ($count < $min) {
				$field['failed_validation'] = true;
				$field['validation_message'] = sprintf(_n('You must select at least %s item.', 'You must select at least %s items.', $min), $min);
				$validation_result['is_valid'] = false;
			} else if ($count > $max) {
				$field['failed_validation'] = true;
				$field['validation_message'] = sprintf(_n('You may only select %s item.', 'You may only select %s items.', $max), $max);
				$validation_result['is_valid'] = false;
			}
		}

		$validation_result['form'] = $form;

		return $validation_result;
	}

	function should_field_be_validated($form, $field)
	{

		if ($field['pageNumber'] != GFFormDisplay::get_source_page($form['id']))
			return false;

		// if no limits provided for this field
		if (!$this->get_field_limits($field['id']))
			return false;

		// or if this field is not a checkbox
		if (RGFormsModel::get_input_type($field) != 'checkbox')
			return false;

		// or if this field is hidden
		if (RGFormsModel::is_field_hidden($form, $field, array()))
			return false;

		return true;
	}

	function get_field_limits($field_id)
	{

		foreach ($this->field_limits as $key => $options) {
			if (in_array($field_id, $options['field']))
				return $options;
		}

		return false;
	}

	function set_field_limits($field_limits)
	{

		foreach ($field_limits as $key => &$options) {

			if (isset($options['field'])) {
				$ids = is_array($options['field']) ? $options['field'] : array($options['field']);
			} else {
				$ids = array($key);
			}

			$options['field'] = $ids;
		}

		return $field_limits;
	}
}

new GFLimitCheckboxes(3, array(
	86 => array(
		'min' => 1,
		'max' => 3
	),
	87 => array(
		'min' => 1,
		'max' => 3
	),
	88 => array(
		'min' => 1,
		'max' => 3
	),
	90 => array(
		'min' => 1,
		'max' => 3
	),
));



// Desactiver les duplicates pour la modification
add_filter('gform_is_duplicate_3', 'desactiver_regle_modification', 10, 4);
function desactiver_regle_modification($count, $form_id, $field, $value)
{
	if (strpos($_SERVER["REQUEST_URI"], "/compte/") !== false) {
		return 0;
	}
}

// Shortcode pour afficher le formulaire de candidature pour les membres
add_shortcode('changer_candidature', 'changer_candidature_shortcode');
function changer_candidature_shortcode()
{
	ob_start();

	// don't show form to admins
	if (!current_user_can('subscriber')) {
		echo '<script>window.location.href="/extranet"</script>';
	} else {
		// grab entry id from user meta
		$entry_id = get_user_meta(get_current_user_id(), 'form_entry_id', true);

		// grab the entry values via the GF API
		$entry = GFAPI::get_entry($entry_id);

		if (is_wp_error($entry)) {
			echo '<p class="admin-msg">Il semble n\'y avoir aucun formulaire de candidature associé à votre compte.</p>';
		} else {
			$resume_file = $entry['85'];
			$fields = array(
				'first_name'   => $entry['1'],
				'last_name'    => $entry['45'],
				'email'        => $entry['55'],
				'phone'        => $entry['56'],
				// 'birth_date'   => $entry['29'],
				'birth_year'   => $entry['91'],
				'birth_month'  => $entry['92'],
				'birth_day'    => $entry['93'],
				'gender'       => $entry['52'],
				'pronouns'     => $entry['53'],
				'region'       => $entry['54'],
				'postal'       => $entry['57'],
				'city'         => $entry['58'],
				'groupe'       => array(
					$entry['59.1'],
					$entry['59.2'],
					$entry['59.3'],
					$entry['59.4'],
					$entry['59.5'],
					$entry['59.6'],
				),
				'year_of_immigration' => $entry['94'],
				'scolarite'    => $entry['60'],
				'langues'      => json_decode($entry['104']),
				'experience'   => $entry['62'],
				'membre_ordre' => $entry['64'],
				'ordres'       => json_decode($entry['99']),
				'type_org'     => $entry['67'],
				'dom_expert'   => json_decode($entry['100']),
				'sect_exp'     => json_decode($entry['101']),
				'formations'   => $entry['72'],
				'form_gouv'    => array(
					$entry['89.1'],
					$entry['89.2'],
					$entry['89.3'],
					$entry['89.4'],
					$entry['89.5'],
					$entry['89.6'],
					$entry['89.7'],
				),
				'exp_gouv'     => $entry['75'],
				'an_exp_gouv'  => $entry['76'],
				'siege_actu'   => $entry['78'],
				'dom_interet'  => json_decode($entry['98']),
				'linkedin'     => $entry['84'],
				'terms'        => array(
					$entry['26.1'],
				),
			);

			// embed new form and populate the form with the user's data
			gravity_form(3, false, false, false, $fields, false);

			if ($resume_file != '') {
			?>
				<script>
					jQuery(document).on('gform_post_render', function(event, form_id, current_page) {
						const file_input_wrapper = document.querySelector('label[for="input_3_85"]').parentNode;
						// add a paragraph under the file input to link the user to the file
						const file_input_wrapper_p = document.createElement('p');
						file_input_wrapper_p.innerHTML = '<a href="<?php echo $resume_file; ?>" rel="noopener" target="_blank">Voir votre CV actuel</a>';
						file_input_wrapper.appendChild(file_input_wrapper_p);
					});
				</script>
			<?php
			}
			?>
			<script>
				// select birth date
				jQuery(document).on('gform_post_render', function(event, form_id, current_page) {
					document.querySelector('#input_3_91').value = '<?php echo $entry['91']; ?>';
					// document.querySelector('#input_3_92').value = '<?php echo $entry['92']; ?>';
					// document.querySelector('#input_3_93').value = '<?php echo $entry['93']; ?>';
				});
			</script>
	<?php
		}
	}

	return ob_get_clean();
}

add_shortcode('dist_bulk_pdf', 'dist_bulk_pdf_shortcode');
function dist_bulk_pdf_shortcode()
{
	$view = $GLOBALS['gravityview_view'];
	$entries = $view->getEntries();
	$no_results = count($entries) == 0;
	$upload_dir = wp_upload_dir();

	$hash = "";

	if ( isset( $_GET['generate_pdfs'] ) && $_GET['generate_pdfs'] == 1) {
		$entries_pdf = array();

		// Generate a PDF for each entry
		foreach ($entries as $entry) {
			$pdf_path = GPDFAPI::create_pdf($entry['id'], '614f1cb470d4c');
			if (!is_wp_error($pdf_path) && is_file($pdf_path)) {
				$entries_pdf[] = $pdf_path;
			}
		}

		// Zip all the PDFs using ZipArchive
		$zip = new ZipArchive();
		$DelFilePath = "bulk_pdf.zip";
		$filename = $upload_dir['basedir'] . "/temp_zip/" . $DelFilePath;

		if (file_exists($filename)) {
			unlink($filename);
		}
		if ($zip->open($filename, ZipArchive::CREATE) != TRUE) {
			die("Could not open archive");
		}

		foreach ($entries_pdf as $pdf_path) {
			$zip->addFile($pdf_path, basename($pdf_path));
		}
		// close and save archive

		$generate_success = $zip->close();

		$hash = md5_file($filename);
	}

	if ( isset( $_GET['generate_csv'] ) && $_GET['generate_csv'] == 1 ) {
		$user_CSV[0] = array(
			'Date d\'inscription',
			'Lien vers l\'inscription',
			'Prénom',
			'Nom',
			'Courriel',
			'Téléphone',
			// 'Date de naissance',
			'Année de naissance',
			//'Mois de naissance',
			//'Jour de naissance',
			'Je m\'identifie comme',
			'Pronoms',
			'Région',
			'Code postal',
			'Ville',
			'Appartenance à un groupe',
			'Année d\'arrivée au Canada',
			'Niveau d\'études',
			'Langues parlées',
			'Niveau d\'expérience professionnelle',
			'Membre d\'un ordre professionnel',
			'Ordres professionnels',
			'Type d\'organisation',
			'Domaines d\'expertise',
			'Secteurs d\'expérience',
			'A complété une ou des formations',
			'Formations',
			'A de l\'expérience en gouvernance',
			'Années d\'expérience en gouvernance',
			'Siège actuellement',
			'Domaines d\'intérêt',
			'LinkedIn',
			'Curriculum Vitae',
		);

		foreach ($entries as $entry) {
			$groupes = array(
				$entry['59.1'] ? $entry['59.1'] : false,
				$entry['59.2'] ? $entry['59.2'] : false,
				$entry['59.3'] ? $entry['59.3'] : false,
				$entry['59.4'] ? $entry['59.4'] : false,
				$entry['59.5'] ? $entry['59.5'] : false,
				$entry['59.6'] ? $entry['59.6'] : false,
			);
			$groupes = array_filter($groupes);
			$groupes = implode(', ', $groupes);
			$langues = implode(', ', json_decode($entry['104']));
			$ordres = implode(', ', json_decode($entry['99']));
			$dom_expert = implode(', ', json_decode($entry['100']));
			$sect_exp = implode(', ', json_decode($entry['101']));
			$form_gouv = array(
				$entry['89.1'],
				$entry['89.2'],
				$entry['89.3'],
				$entry['89.4'],
				$entry['89.5'],
				$entry['89.6'],
				$entry['89.7'],
			);
			$form_gouv = array_filter($form_gouv);
			$form_gouv = implode(', ', $form_gouv);
			$dom_interet = implode(', ', json_decode($entry['98']));

			$user_CSV[] = array(
				$entry['date_created'],
				site_url( '/view/formulaire-de-candidature/entry/' . $entry['id'] ),
				$entry['1'],
				$entry['45'],
				$entry['55'],
				$entry['56'],
				// $entry['29'],
				$entry['91'],
				//$entry['92'],
				//$entry['93'],
				$entry['52'],
				$entry['53'],
				$entry['54'],
				$entry['57'],
				$entry['58'],
				$groupes,
				$entry['94'],
				$entry['60'],
				$langues,
				$entry['62'],
				$entry['64'],
				$ordres,
				$entry['67'],
				$dom_expert,
				$sect_exp,
				$entry['72'],
				$form_gouv,
				$entry['75'],
				$entry['76'],
				$entry['78'],
				$dom_interet,
				$entry['84'],
				$entry['85'],
			);
		}

		$DelFilePath = "resultats.csv";
		$filename = $upload_dir['basedir'] . "/temp_zip/" . $DelFilePath;
		if (file_exists($filename)) {
			unlink($filename);
		}
		$csv_file = fopen($filename, 'wb');
		fprintf($csv_file, chr(0xEF) . chr(0xBB) . chr(0xBF));
		foreach ($user_CSV as $line) {
			// though CSV stands for "comma separated value"
			// in many countries (including France) separator is ";"
			fputcsv($csv_file, $line, ',');
		}
		$generate_csv_success = fclose($csv_file);

		$hash = md5_file($filename);
	}

	ob_start();
	?>

	<?php if (!$no_results) : ?>
		<a href="" class="button dist_bulk_pdf">
			PDF
		</a>
		<a href="" class="button dist_bulk_csv">
			CSV
		</a>
	<?php endif; ?>

	<?php if ( isset( $_GET['generate_csv'] ) &&  $_GET['generate_csv'] == 1 && $generate_csv_success ) : ?>
		<!-- Télécharger le CSV -->
		<script>
			jQuery(document).ready(function($) {
				$('a.button.dist_bulk_csv').attr('href', window.location.origin + '<?php echo $upload_dir['basedir'] ?>/temp_zip/<?php echo $DelFilePath ?>?<?php echo $hash ?>');
				$('a.button.dist_bulk_csv').text('Télécharger le fichier CSV');
				// Remove get parameter on click
				$('a.button.dist_bulk_csv').click(function(e) {
					setTimeout(() => {
						let tempsearch = window.location.search;
						tempsearch = tempsearch.replace('?generate_pdfs=1', '');
						tempsearch = tempsearch.replace('&generate_pdfs=1', '');
						tempsearch = tempsearch.replace('?generate_csv=1', '');
						tempsearch = tempsearch.replace('&generate_csv=1', '');

						window.location.href = window.location.origin + window.location.pathname + tempsearch;
					}, 250);
				});
			});
		</script>
	<?php else : ?>
		<!-- Générer le CSV -->
		<script>
			jQuery(document).ready(function($) {
				let newGet;
				if (window.location.search === '') {
					newGet = '?generate_csv=1';
				} else {
					newGet = window.location.search + '&generate_csv=1';
				}
				$('a.button.dist_bulk_csv').attr('href', window.location.origin + window.location.pathname + newGet);
				$('a.button.dist_bulk_csv').text('Générer le fichier CSV');
			});
		</script>
	<?php endif; ?>

	<?php if ( isset( $_GET['generate_pdfs'] ) && $_GET['generate_pdfs'] == 1 && $generate_success ) : ?>
		<!-- Télécharger le PDF -->
		<script>
			jQuery(document).ready(function($) {
				$('a.button.dist_bulk_pdf').attr('href', window.location.origin + '/wp-content/uploads/temp_zip/bulk_pdf.zip?<?php echo $hash ?>');
				$('a.button.dist_bulk_pdf').text('Télécharger les fichiers PDF');
				// Remove get parameter on click
				$('a.button.dist_bulk_pdf').click(function(e) {
					setTimeout(() => {
						let tempsearch = window.location.search;
						tempsearch = tempsearch.replace('?generate_pdfs=1', '');
						tempsearch = tempsearch.replace('&generate_pdfs=1', '');
						tempsearch = tempsearch.replace('?generate_csv=1', '');
						tempsearch = tempsearch.replace('&generate_csv=1', '');

						window.location.href = window.location.origin + window.location.pathname + tempsearch;
					}, 250);
				});
			});
		</script>
	<?php else : ?>
		<!-- Générer le PDF -->
		<script>
			jQuery(document).ready(function($) {
				let newGet;
				if (window.location.search === '') {
					newGet = '?generate_pdfs=1';
				} else {
					newGet = window.location.search + '&generate_pdfs=1';
				}
				$('a.button.dist_bulk_pdf').attr('href', window.location.origin + window.location.pathname + newGet);
				$('a.button.dist_bulk_pdf').text('Générer les fichiers PDF');
			});
		</script>
	<?php endif; ?>

<?php
	return ob_get_clean();
}

// Si l'utilisateur met à jour son profil, on le met à jour
add_action("gform_pre_submission_3", "pre_submission_handler");
function pre_submission_handler($form)
{
	global $_gf_uploaded_files;

	if (strpos($_SERVER["REQUEST_URI"], "/compte/") !== false) {
		// grab entry id from user meta
		$form_id = 3;
		$entry_id = get_user_meta(get_current_user_id(), 'form_entry_id', true);

		// Moves the uploaded files to the correct folder
		$file_info     = GFFormsModel::get_temp_filename($form_id, 'input_85');
		$temp_filename = rgar($file_info, 'temp_filename', '');
		$temp_filepath = GFFormsModel::get_upload_path($form_id) . '/tmp/' . $temp_filename;

		if ($file_info && file_exists($temp_filepath)) {
			$target = GFFormsModel::get_file_upload_path($form_id, $file_info['uploaded_filename']);
			$source = GFFormsModel::get_upload_path($form_id) . '/tmp/' . wp_basename($file_info['temp_filename']);

			if (rename($source, $target['path'])) {
				GFFormsModel::set_permissions($target['path']);

				$_gf_uploaded_files['input_85'] = $target['url'];
			}
		}

		// submitted new values that need to be used to update the original entry via $success = GFAPI::update_entry( $entry );
		// echo '<pre>';
		// var_dump($form);
		// var_dump($_POST);
		// var_dump($_FILES);
		// echo '</pre>';

		// get the actual entry we want to edit
		$editentry = GFAPI::get_entry($entry_id);

		// make changes to it from new values in $_POST, this shows only the first field update
		$editentry['1']  = sanitize_input( $_POST["input_1"] ); // first name
		$editentry['45'] = sanitize_input( $_POST["input_45"] ); // last name
		$editentry['55'] = sanitize_input( $_POST["input_55"] ); // email
		$editentry['56'] = sanitize_input( $_POST["input_56"] ); // phone
	  //$editentry['29'] = sanitize_input( $_POST["input_29"] ); // birth date
		$editentry['91'] = sanitize_input( $_POST["input_91"] ); // birth year
		$editentry['92'] = sanitize_input( $_POST["input_92"] ); // birth month
		$editentry['93'] = sanitize_input( $_POST["input_93"] ); // birth day
		$editentry['52'] = sanitize_input( $_POST["input_52"] ); // gender
		$editentry['53'] = sanitize_input( $_POST["input_53"] ); // pronouns
		$editentry['54'] = sanitize_input( $_POST["input_54"] ); // region
		$editentry['57'] = sanitize_input( $_POST["input_57"] ); // postal
		$editentry['58'] = sanitize_input( $_POST["input_58"] ); // city

		// groupe
		$editentry['59.1'] = array_key_exists('input_59_1', $_POST) ? sanitize_input( $_POST["input_59_1"] ) : '';
		$editentry['59.2'] = array_key_exists('input_59_2', $_POST) ? sanitize_input( $_POST["input_59_2"] ) : '';
		$editentry['59.3'] = array_key_exists('input_59_3', $_POST) ? sanitize_input( $_POST["input_59_3"] ) : '';
		$editentry['59.4'] = array_key_exists('input_59_4', $_POST) ? sanitize_input( $_POST["input_59_4"] ) : '';
		$editentry['59.5'] = array_key_exists('input_59_5', $_POST) ? sanitize_input( $_POST["input_59_5"] ) : '';
		$editentry['59.6'] = array_key_exists('input_59_6', $_POST) ? sanitize_input( $_POST["input_59_6"] ) : '';

		$editentry['94']  = sanitize_input( $_POST["input_94"] ); // annee d'arrivee au Canada

		$editentry['60']  = sanitize_input( $_POST["input_60"] ); // scolarite

		$editentry['104'] = json_encode( sanitize_input( $_POST["input_104"] ) ); // langues

		$editentry['62']  = sanitize_input( $_POST["input_62"] ); // experience
		$editentry['64']  = sanitize_input( $_POST["input_64"] ); // membre d'un ordre?

		$editentry['99']  = json_encode( sanitize_input( $_POST["input_99"] ) ); // ordres

		$editentry['67']  = sanitize_input( $_POST["input_67"] ); // type d'organisation où travaille

		$editentry['100'] = json_encode( sanitize_input( $_POST["input_100"] ) ); // domaine d'expertise

		$editentry['101'] = json_encode( sanitize_input( $_POST["input_101"] ) ); // secteur d'expérience significative

		$editentry['72']  = sanitize_input( $_POST["input_72"] ); // formations en gouvernance?

		// formations
		$editentry['89.1'] = array_key_exists('input_89_1', $_POST) ? sanitize_input( $_POST["input_89_1"] ) : '';
		$editentry['89.2'] = array_key_exists('input_89_2', $_POST) ? sanitize_input( $_POST["input_89_2"] ) : '';
		$editentry['89.3'] = array_key_exists('input_89_3', $_POST) ? sanitize_input( $_POST["input_89_3"] ) : '';
		$editentry['89.4'] = array_key_exists('input_89_4', $_POST) ? sanitize_input( $_POST["input_89_4"] ) : '';
		$editentry['89.5'] = array_key_exists('input_89_5', $_POST) ? sanitize_input( $_POST["input_89_5"] ) : '';
		$editentry['89.6'] = array_key_exists('input_89_6', $_POST) ? sanitize_input( $_POST["input_89_6"] ) : '';
		$editentry['89.7'] = array_key_exists('input_89_7', $_POST) ? sanitize_input( $_POST["input_89_7"] ) : '';

		$editentry['75'] = sanitize_input( $_POST["input_75"] ); // experience en gouvernance
		$editentry['76'] = sanitize_input( $_POST["input_76"] ); // annees d'experience
		$editentry['78'] = sanitize_input( $_POST["input_78"] ); // siege presentement?

		$editentry['98'] = json_encode( sanitize_input( $_POST["input_98"] ) ); // domaine d'interets

		$editentry['84']   = sanitize_input( $_POST["input_84"] ); // linkedin
		$editentry['85']   = $target['url']; // resume
		$editentry['26.1'] = array_key_exists('input_26_1', $_POST) ? sanitize_input( $_POST["input_26_1"] ) : ''; // terms

		// update it
		$updateit = GFAPI::update_entry($editentry);

		if (!is_wp_error($updateit)) {
			// update user data with new values
			$userdata = array(
				'ID' => get_current_user_id(),
				'user_login' => sanitize_input( $_POST["input_55"] ),
				'user_email' => sanitize_input( $_POST["input_55"] ),
				'first_name' => sanitize_input( $_POST["input_1"] ),
				'last_name'  => sanitize_input( $_POST["input_45"] ),
			);
			$user_id = wp_update_user($userdata);
			if (!is_wp_error($user_id)) {
				// success, so redirect
				header( "Location: " . site_url("/compte/") );
			} else {
				// error, so redirect
				header( "Location: " . site_url("/compte/") );
			}
		} else {
			echo "Error.";
		}

		// dont process and create new entry
		die();
	} else {
		// any other code you want in this hook for regular entry submit
	}
}


add_action('gform_field_validation_3_55', 'check_if_email_exists', 10, 4);
function check_if_email_exists($result, $value, $form, $field)
{
	$is_update = strpos($_SERVER["REQUEST_URI"], "/compte/") !== false;
	if (!$is_update && $result['is_valid'] && email_exists($value)) {
		$result['is_valid'] = false;
		$result['message'] = 'Ce courriel est déjà pris! Veuillez vous connecter à votre compte, ou utiliser un autre courriel.';
	}
	return $result;
}

// Quand quelqu'un rempli le formulaire de candidature (ID 3), on lui crée un compte
add_action('gform_after_submission_3', 'create_user_from_submission', 10, 2);
function create_user_from_submission($entry, $form)
{
	// On récupère les données du formulaire Gravity Forms
	$entry_id = $entry['id'];
	$first_name = rgar($entry, '1');
	$last_name = rgar($entry, '45');
	$email = rgar($entry, '55');
	$password = wp_generate_password(16);

	// On crée l'utilisateur
	$userdata = array(
		'user_login' => $email,
		'user_pass' => $password,
		'user_email' => $email,
		'first_name' => $first_name,
		'last_name' => $last_name,
		'role' => 'subscriber'
	);
	$user_id = wp_insert_user($userdata);

	// Si l'utilisateur a été créé, on ajoute un meta qui indique son formulaire de candidature
	// et on lui envoie un mail de confirmation
	if (!is_wp_error($user_id)) {
		update_user_meta($user_id, 'form_entry_id', $entry_id);
		update_user_meta($user_id, '_new_user', true);

		$message = '<p>Bonjour ' . $first_name . ',<br>';
		$message .= 'Merci d’avoir complété le formulaire d’inscription à la banque de candidatures de Concertation Montréal, veuillez vous connecter à votre compte personnel afin de valider votre inscription. Sans cela, votre inscription dans notre Banque ne sera pas confirmée.</p>';
		$message .= '<p>Votre identifiant est : <strong>' . $email . '</strong><br>';
		$message .= 'Votre mot de passe est : <strong>' . $password . '</strong></p>';
		$message .= '<p>Vous pouvez vous connecter à l\'adresse suivante : <a href="' . get_site_url() . '/connexion">' . get_site_url() . '/connexion</a></p>';
		$message .= '<p>Vous pourrez également accéder en tout temps à votre profil pour mettre à jour vos informations ou supprimer votre profil.</p>';
		$message .= '<p>Cordialement,<br>';
		$message .= 'L\'équipe de Concertation Montréal</p>';
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: Sonia Malek <smalek@concertationmtl.ca>'
		);

		wp_mail($email, ' [ACTION REQUISE] Vous devez valider votre inscription dans la Banque de candidatures Concertation Montréal', $message, $headers);
	}
}

// Quand l'utilisateur se connecte pour la première fois, on approuve son entrée de formulaire
function approve_entry()
{
	if (is_user_logged_in()) {
		$user_id = get_current_user_id();
		$new_user = get_user_meta($user_id, '_new_user', true);
		$entry_id = get_user_meta($user_id, 'form_entry_id', true);
		if ($entry_id && $new_user) {
			$entry = GFAPI::get_entry($entry_id);
			$entry['is_approved'] = 1;
			GFAPI::update_entry($entry);
			delete_user_meta($user_id, '_new_user');
		}
	}
}
add_action('wp_login', 'approve_entry');

add_filter('gravityview-importer/strict-mode/fill-checkbox-choices', '__return_true');


// Strip tags and remove or encode special characters from a string
function sanitize_input( $input ) {
	if ( is_array( $input ) ) {
        foreach ( $input as $key => $value ) {
            $input[$key] = sanitize_input( $value );
        }
    } else {
        $input = filter_var( $input, FILTER_SANITIZE_STRING );
    }
    return $input;
}


function delete_account() {
	if ( is_user_logged_in() && isset( $_POST['confirm'] ) ) {
		// only if role is subscriber 2
		$user = wp_get_current_user();
		if (
			!empty( $user->roles ) &&
			is_array( $user->roles ) &&
			in_array( 'subscriber', $user->roles )
		) {
			$entry_id = get_user_meta( get_current_user_id(), 'form_entry_id', true );
		
			// delete entry
			$no_gf = false;
			if ( $entry_id ) {
			$delete_gf = GFAPI::delete_entry( $entry_id );
			if ( is_wp_error( $delete_gf ) ) {
				$error_message = $delete_gf->get_error_message();
			}
			} else {
				$no_gf = true;
			}
		
			// delete user
			$del_user = wp_delete_user( get_current_user_id() );
		
			if ( (! is_wp_error( $delete_gf ) || $no_gf ) && $del_user ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( array( 'error' => $error_message ) );
			}
		}
	}
}
add_action('wp_ajax_delete_account', 'delete_account');
add_action('wp_ajax_nopriv_delete_account', 'delete_account');