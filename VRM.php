<?php
/**
* @package GGVrmLookup
*/
/*
Plugin Name: GG VRM Lookup
Description: Lookup an entered Vehicle Registration Number and find associated products.
Version: 1.0.0
Author: Globalgraphics
Author URI: https://www.globalgraphics.co.uk
*/

if (!defined('ABSPATH')) {
	die;
}

add_action( 'admin_menu', 'ggVRMLookup_admin_menu' );
function ggVRMLookup_admin_menu() {
	add_menu_page( 'Roof Box Lookup', 'Roof Box Lookup', 'manage_options', 'admin-page', 'ggVRMLookup_admin_page' );
}

function ggVRMLookup_safeText($ggVRMLookup_conn,$text,$wysiwyg=false) {
	if ($wysiwyg) return mysqli_real_escape_string($ggVRMLookup_conn,$text);
	else return mysqli_real_escape_string($ggVRMLookup_conn,preg_replace("/[<>]/", "",urldecode(urldecode($text))));
}

function ggVRMLookup_admin_page() {
	
	if (isset($_GET['act']) && $_GET['act']=='save') {
		$failed = true;
		
		foreach ($_FILES as $file) {
			$basename = strtolower(basename($file['name']));
			$basename_arr = explode(".",$basename);
			$extension = end($basename_arr);
			$row = 0;
			
			//Fields
			$field_manufacturer = 0;
			$field_model = 1;
			$field_doors = 2;
			$field_year = 3;
			$field_no_rails_1 = 4;
			$field_no_rails_2 = 5;
			$field_no_rails_3 = 6;
			$field_rails_1 = 7;
			$field_rails_2 = 8;
			$field_rails_3 = 9;
			$field_rails_4 = 10;
			$field_rails_5 = 11;
			$field_rails_6 = 12;			
			
			if ($file['tmp_name'] > '') {
				if ($extension=='csv') {
					
					$ggVRMLookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
					if (!$ggVRMLookup_conn) {
					echo 'Unable to connect to DB: ' . mysqli_error();
					exit;
					}
					if (!mysqli_select_db($ggVRMLookup_conn,DB_NAME)) {
					echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
					exit;
					}
					mysqli_query($ggVRMLookup_conn,"SET NAMES utf8");
					
					$sql = "DELETE FROM ggvrmlu_cars_to_products";
					mysqli_query($ggVRMLookup_conn,$sql);
					
					if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
						while (($data = fgetcsv($handle, 0)) !== FALSE) {
							$row++;
							if ($row>1) {
								$manufacturer = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_manufacturer])));
								$model = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_model])));
								$doors = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_doors]));
								$year = trim($data[$field_year]);
								$no_rails_1 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_no_rails_1]));
								$no_rails_2 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_no_rails_2]));
								$no_rails_3 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_no_rails_3]));
								$rails_1 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_rails_1]));
								$rails_2 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_rails_2]));
								$rails_3 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_rails_3]));
								$rails_4 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_rails_4]));
								$rails_5 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_rails_5]));
								$rails_6 = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_rails_6]));
								
								$year_arr = explode('>',trim($year));
								$year_start = (isset($year_arr[0]) ? $year_arr[0] : '');
								$year_end = (isset($year_arr[1]) ? $year_arr[1] : '');
								
								$year_start = intval(intval($year_start)>80 ? '19'.$year_start : '20'.$year_start);
								if ($year_end=='') $year_end = 9999;
								if ($year_end!=9999) $year_end = intval(intval($year_end)>80 ? '19'.$year_end : '20'.$year_end);
								
								$year_start = ggVRMLookup_safeText($ggVRMLookup_conn,$year_start);
								$year_end = ggVRMLookup_safeText($ggVRMLookup_conn,$year_end);

								if ($doors=='') $doors = 0;
								
								$sql = "INSERT INTO ggvrmlu_cars_to_products(manufacturer,model,doors,year_start,year_end,no_rails_1,no_rails_2,no_rails_3,rails_1,rails_2,rails_3,rails_4,rails_5,rails_6) VALUES ('$manufacturer','$model','$doors','$year_start','$year_end','$no_rails_1','$no_rails_2','$no_rails_3','$rails_1','$rails_2','$rails_3','$rails_4','$rails_5','$rails_6')";
								mysqli_query($ggVRMLookup_conn,$sql);
							}
						}
						$failed = false;
					}
					mysqli_close($ggVRMLookup_conn);
				}
			}
		}
		
		if ($failed) echo '<h1>Upload New Cars-to-Products CSV</h1><p>Import failed.  Please try again.</p>';
		if (!$failed) echo '<h1>Upload Successful</h1><p>Your data has been imported.</p>';
	}
	else {
		?>
		<h1>Upload New Cars-to-Products CSV</h1>
		<form action="/wp-admin/admin.php?page=admin-page&act=save" method="post" enctype="multipart/form-data">
			<p>Select your CSV file below to upload and import.  All previous cars-to-products data will be deleted.</p>
			<input type="file" name="file" placeHolder="Select CSV to Upload" /><br /><br />
			<input type="submit" value="Upload" />
		</form>
		<?php
	}
}

function vrm_dropdown_options() {
	$vrm_lookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
	if (!$vrm_lookup_conn) {
		echo 'Unable to connect to DB: ' . mysqli_error();
		exit;
	}
	if (!mysqli_select_db($vrm_lookup_conn,DB_NAME)) {
		echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
		exit;
	}
	mysqli_query($vrm_lookup_conn,"SET NAMES utf8");

	ob_start();

	//if ($_GET['global']=='graphics') {
	?>
	<form action="/vehicle-lookup/" method="post" class="vrm_dropdown_form">
		<h3>Or select your car below...</h3>
		<div class="vc_row vrm_dropdown_options">
			<div class="vc_col-md-3">
				<?php
				$sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
				?>
					<select name="vrm_manufacturer" id="vrm_manufacturer">
						<option value="">- Select Make -</option>
						<?php while ($row1 = mysqli_fetch_assoc($rs1)) {?>
							<option value="<?php echo stripslashes($row1['manufacturer'])?>"><?php echo stripslashes($row1['manufacturer'])?></option>
						<?php } ?>
					</select>
				<?php
				}
				mysqli_free_result($rs1);
				?>
			</div>
			<div class="vc_col-md-3">
				<select name="vm__blank" id="vm__blank" class="vrm_models">
					<option value="" class="blank">- Select Model -</option>
				</select>
				<?php $sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
					while ($row1 = mysqli_fetch_assoc($rs1)) {
						$manufacturer_field = vrm_safeField(stripslashes($row1['manufacturer']));
						$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' ORDER BY model";
						$rs2 = mysqli_query($vrm_lookup_conn,$sql);
						if (mysqli_num_rows($rs2)>0){?>
							<select name="vm_<?php echo $manufacturer_field?>" id="vm_<?php echo $manufacturer_field?>" class="vrm_models">
								<option value="" class="blank">- Select Model -</option>
								<?php while ($row2 = mysqli_fetch_assoc($rs2)) {?>
									<option value="<?php echo stripslashes($row2['model'])?>"><?php echo stripslashes($row2['model'])?></option>
								<?php }?>
							</select>
						<?php }
						mysqli_free_result($rs2);
					}
				}
				mysqli_free_result($rs1);
				?>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('.vrm_models').hide();
					jQuery('#vm__blank').show();

					jQuery('#vrm_manufacturer').on('change',function(){
						jQuery('.vrm_models').hide();
						jQuery('#vm_'+jQuery('#vrm_manufacturer').val().replace(/\(/g,'').replace(/\)/g,'').replace(/\s/g,'_')).show();

						jQuery('.vrm_years').hide();
						jQuery('#vy__blank').show();
						jQuery('.vrm_doors').hide();
						jQuery('#vd__blank').show();
					});
				});
				</script>
			</div>
			<div class="vc_col-md-3">
				<select name="vy__blank" id="vy__blank" class="vrm_years">
					<option value="" class="blank">- Select Year -</option>
				</select>
				<?php
				$sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
					while ($row1 = mysqli_fetch_assoc($rs1)) {
						$manufacturer_field = vrm_safeField(stripslashes($row1['manufacturer']));
						$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' ORDER BY model";
						$rs2 = mysqli_query($vrm_lookup_conn,$sql);
						if (mysqli_num_rows($rs2)>0){
							while ($row2 = mysqli_fetch_assoc($rs2)) {
								$model_field = vrm_safeField(stripslashes($row2['model']));
								$sql = "SELECT year_start, year_end FROM ggvrmlu_cars_to_products WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' AND model='".mysqli_real_escape_string($vrm_lookup_conn,$row2['model'])."' ORDER BY year_start";
								$rs3 = mysqli_query($vrm_lookup_conn,$sql);
								echo mysqli_error($vrm_lookup_conn);
								if (mysqli_num_rows($rs3)>0){?>
									<select name="vy_<?php echo $manufacturer_field?>_<?php echo $model_field?>" id="vy_<?php echo $manufacturer_field?>_<?php echo $model_field?>" class="vrm_years">
										<option value="" class="blank">- Select Year -</option>
										<?php while ($row3 = mysqli_fetch_assoc($rs3)) {?>
											<option value="<?php echo stripslashes($row3['year_start'])?>"><?php echo stripslashes($row3['year_start'])?> > <?php echo ($row3['year_end']==9999 ? '' : stripslashes($row3['year_end']))?></option>
										<?php }?>
									</select>
								<?php }
								mysqli_free_result($rs3);
							}
						}
						mysqli_free_result($rs2);
					}
				}
				mysqli_free_result($rs1);
				?>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('.vrm_years').hide();
					jQuery('#vy__blank').show();

					jQuery('.vrm_models').on('change',function(){
						jQuery('.vrm_years').hide();
						var manufacturer_field = jQuery('#vrm_manufacturer').val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_');
						var model_field = jQuery('#vm_'+manufacturer_field).val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_');
						jQuery('#vy_'+manufacturer_field+'_'+model_field).show();

						jQuery('.vrm_doors').hide();
						jQuery('#vd__blank').show();
					});
				});
				</script>
			</div>
			<div class="vc_col-md-3">
				<select name="vd__blank" id="vd__blank" class="vrm_doors">
					<option value="" class="blank">- Select Doors -</option>
				</select>
				<?php
				$sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
					while ($row1 = mysqli_fetch_assoc($rs1)) {
						$manufacturer_field = vrm_safeField(stripslashes($row1['manufacturer']));
						$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' ORDER BY model";
						$rs2 = mysqli_query($vrm_lookup_conn,$sql);
						if (mysqli_num_rows($rs2)>0){
							while ($row2 = mysqli_fetch_assoc($rs2)) {
								$model_field = vrm_safeField(stripslashes($row2['model']));
								$sql = "SELECT year_start, year_end FROM ggvrmlu_cars_to_products WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' AND model='".mysqli_real_escape_string($vrm_lookup_conn,$row2['model'])."' ORDER BY year_start";
								$rs3 = mysqli_query($vrm_lookup_conn,$sql);
								echo mysqli_error($vrm_lookup_conn);
								if (mysqli_num_rows($rs3)>0){
									while ($row3 = mysqli_fetch_assoc($rs3)) {
										$year_start = vrm_safeField(stripslashes($row3['year_start']));
										$sql = "SELECT doors FROM ggvrmlu_cars_to_products WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' AND model='".mysqli_real_escape_string($vrm_lookup_conn,$row2['model'])."' AND year_start='".mysqli_real_escape_string($vrm_lookup_conn,$row3['year_start'])."' ORDER BY doors";
										$rs4 = mysqli_query($vrm_lookup_conn,$sql);
										if (mysqli_num_rows($rs4)>0){?>
											<select name="vd_<?php echo $manufacturer_field?>_<?php echo $model_field?>_<?php echo $year_start?>" id="vd_<?php echo $manufacturer_field?>_<?php echo $model_field?>_<?php echo $year_start?>" class="vrm_doors">
												<option value="" class="blank">- Select Doors -</option>
												<?php while ($row4 = mysqli_fetch_assoc($rs4)) {?>
													<option value="<?php echo stripslashes($row4['doors'])?>"><?php echo stripslashes($row4['doors'])?></option>
												<?php } ?>
											</select>
										<?php }
										mysqli_free_result($rs4);
									}
								}
								mysqli_free_result($rs3);
							}
						}
						mysqli_free_result($rs2);
					}
				}
				mysqli_free_result($rs1);
				?>
				<button type="submit">Go</button>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('.vrm_doors').hide();
					jQuery('#vd__blank').show();

					jQuery('.vrm_years').on('change',function(){
						jQuery('.vrm_doors').hide();
						var manufacturer_field = jQuery('#vrm_manufacturer').val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_');
						var model_field = jQuery('#vm_'+manufacturer_field).val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_');
						var year_field = jQuery('#vy_'+manufacturer_field+'_'+model_field).val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_');
						jQuery('#vd_'+manufacturer_field+'_'+model_field+'_'+year_field).show();
					});
				});
				</script>
			</div>
		</div>
	</form>
	<?php
	$return = ob_get_clean();
	return $return;
}
add_shortcode('vrmdropdownoptions', 'vrm_dropdown_options');

function vrm_lookup_form() {
	ob_start();
	?>
	<div class="vrm-lookup-form">
		<h2>Enter Your Car Registration Number</h2>
		<p>Enter your vehicle registration number to find which of our roof bars will fit your car.</p>
		<form action="/vehicle-lookup/" method="get">
			<input type="text" name="reg" placeHolder="AA11 ZZZ" value="<?php echo (isset($_GET['reg']) ? strtoupper($_GET['reg']) : '')?>" /><button type="submit">Go</button>
		</form>
	</div>
	<?php
	$return = ob_get_clean();
	return $return;
}
add_shortcode('vrmlookupform', 'vrm_lookup_form');

function vrm_get_metafield($vrm_lookup_conn,$post_id,$key) {
	$sql = "SELECT meta_value FROM e3hg_postmeta WHERE meta_key='$key' AND post_id='$post_id'";
	$rs = mysqli_query($vrm_lookup_conn,$sql);
	if ($row = mysqli_fetch_assoc($rs)) {
		return $row['meta_value'];
	}
	mysqli_free_result($rs);
}

function vrm_get_postid($vrm_lookup_conn,$key,$value) {
	$sql = "SELECT post_id FROM e3hg_postmeta WHERE meta_key='$key' AND meta_value='$value'";
	$rs = mysqli_query($vrm_lookup_conn,$sql);
	if ($row = mysqli_fetch_assoc($rs)) {
		return $row['post_id'];
	}
	mysqli_free_result($rs);
}

function vrm_get_postfield($vrm_lookup_conn,$post_id,$field) {
	$sql = "SELECT $field AS value FROM e3hg_posts WHERE id='$post_id' AND post_type='product'";
	$rs = mysqli_query($vrm_lookup_conn,$sql);
	if ($row = mysqli_fetch_assoc($rs)) {
		return $row['value'];
	}
	mysqli_free_result($rs);
}

function vrm_get_product($vrm_lookup_conn,$sku) {
	ob_start();

	$post_id = vrm_get_postid($vrm_lookup_conn,'_sku',$sku);
	if ($post_id!='') {
		$title = vrm_get_postfield($vrm_lookup_conn,$post_id,'post_title');
		$status = vrm_get_postfield($vrm_lookup_conn,$post_id,'post_status');
		$url = 'https://www.maypoleltd.com/product/'.vrm_get_postfield($vrm_lookup_conn,$post_id,'post_name').'/';
		$price = vrm_get_metafield($vrm_lookup_conn,$post_id,'_price');
		$thumbnail = vrm_get_metafield($vrm_lookup_conn,$post_id,'_thumbnail_id');
		?>
		<div class="vc_col-lg-3 vc_col-md-4 vc_col-sm-6 vrm_product">
			<div class="vrm_product_inner">
				<?php $image = wp_get_attachment_image_src($thumbnail,'single-post-thumbnail') ?>
				<a href="<?php echo $url?>"><img src="<?php  echo $image[0]; ?>" data-id="<?php echo $post_id; ?>"></a>	
				<div class="item-description">
					<h3 class="title"><a href="<?php echo $url?>"><?php echo $title?></a></h3>
					<?php /*
					<span class="price"><?php echo get_woocommerce_currency_symbol().$price ?></span>
					<a rel="nofollow" href="/product-category/roof-bars/?add-to-cart=<?php echo $post_id; ?>" data-quantity="1" data-product_id="<?php echo $post_id; ?>" data-product_sku="<?php echo $sku?>" class="button product_type_simple add_to_cart_button ajax_add_to_cart">
					<span class="ftc-tooltip button-tooltip">Add To Basket</span></a>
					*/?>
				</div>	
			</div>
		</div>
		<?php
	}
	$return = ob_get_clean();
	return $return;
}

function vrm_display_car_details($make,$model,$year,$doors) {
	ob_start();
	?>
	<div class="vrm_car_details">
		<h3>Your car details</h3>
		<div class="vc_row">
			<?php if ($make!=''):?><div class="vc_col-lg-3 vc_col-md-6"><strong>Make:</strong> <?php echo $make?></div><?php endif;?>
			<?php if ($model!=''):?><div class="vc_col-lg-3 vc_col-md-6"><strong>Model:</strong> <?php echo $model?></div><?php endif;?>
			<?php if ($year!=''):?><div class="vc_col-lg-3 vc_col-md-6"><strong>Year:</strong> <?php echo $year?></div><?php endif;?>
			<?php if ($doors!='' && $doors!=0):?><div class="vc_col-lg-3 vc_col-md-6"><strong>Doors:</strong> <?php echo $doors?></div><?php endif;?>
		</div>
	</div>
	<?php
	$return = ob_get_clean();
	return $return;
}

function vrm_safeText($ggVRMLookup_conn,$text,$wysiwyg=false) {
	if ($wysiwyg) return mysqli_real_escape_string($ggVRMLookup_conn,$text);
	else return mysqli_real_escape_string($ggVRMLookup_conn,preg_replace("/[<>]/", "",urldecode(urldecode($text))));
}

function vrm_safeField($text) {
	return str_replace(',','_',str_replace(' ','_',str_replace('(','',str_replace(')','',$text))));
}

function vrm_lookup() {
	ob_start();

	$failed = true;

	if (isset($_GET['reg']) || isset($_POST['vrm_manufacturer'])) {

		$vrm_lookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
		if (!$vrm_lookup_conn) {
			echo 'Unable to connect to DB: ' . mysqli_error();
			exit;
		}
		if (!mysqli_select_db($vrm_lookup_conn,DB_NAME)) {
			echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
			exit;
		}
		mysqli_query($vrm_lookup_conn,"SET NAMES utf8");

		$year_text = '';

		if (isset($_GET['vrm_manufacturer']) && isset($_GET['vrm_model']) && isset($_GET['vrm_year']) && isset($_GET['vrm_doors'])) {
			$manufacturer = vrm_safeText($vrm_lookup_conn,$_GET['vrm_manufacturer']);
			$model = vrm_safeText($vrm_lookup_conn,$_GET['vrm_model']);
			$year = vrm_safeText($vrm_lookup_conn,$_GET['vrm_year']);
			$doors = vrm_safeText($vrm_lookup_conn,$_GET['vrm_doors']);

			$model_text = "model='$model'";
			if (isset($_GET['reg'])) $year_text = "year_start<='$year' AND year_end>='$year'";
			else $year_text = "year_start='$year'";	
		}
		else if (isset($_POST['vrm_manufacturer'])) {
			$manufacturer = vrm_safeText($vrm_lookup_conn,$_POST['vrm_manufacturer']);
			$manufacturer_field = vrm_safeField($manufacturer);
			$model = vrm_safeText($vrm_lookup_conn,$_POST['vm_'.$manufacturer_field]);
			$model_field = vrm_safeField($model);
			$year = vrm_safeText($vrm_lookup_conn,$_POST['vy_'.$manufacturer_field.'_'.$model_field]);
			$year_field = vrm_safeField($year);
			$doors = vrm_safeText($vrm_lookup_conn,$_POST['vd_'.$manufacturer_field.'_'.$model_field.'_'.$year_field]);
			$doors_field = vrm_safeField($doors);

			$model_text = "model='$model'";
			$year_text = "year_start='$year'";
		}
		else {
			$reg = $_GET['reg'];
			
			$feedname = 'MAYPOLE';
			$username = 'MAYPOLE';
			$password = urlencode("RG'TwNMxF7VYd0VJ2,r*Ok<;5*DEF.pk7zQ_ZVUf");

			$postfields = "vrm=$reg&mileage=&feedName=$feedname&versionNumber=2&userName=$username&password=$password";

			$Curl_Session = curl_init('https://jarvis.cdlis.co.uk/jarvis-webapp/search');
			curl_setopt($Curl_Session, CURLOPT_RETURNTRANSFER,1);
			curl_setopt ($Curl_Session, CURLOPT_POST, 1);
			curl_setopt ($Curl_Session, CURLOPT_POSTFIELDS, $postfields);
			$data = curl_exec ($Curl_Session);
			curl_close ($Curl_Session);	
	
			//print_r($data);

			$xml = simplexml_load_string($data);
			
			if ((isset($xml->code) && $xml->code=='404') || strlen($data)==0) {
				//do an irish plate lookup
				$newreg = strtoupper(str_replace(' ','',$_GET['reg']));
				$Curl_Session1 = curl_init('https://api.motorcheck.ie/vehicle/reg/'.$newreg.'/basic?_username=maypole-ltd&_api_key=32f9c0dabfc545bb93c78df89c7e696d96cd5d61');
				curl_setopt($Curl_Session1, CURLOPT_RETURNTRANSFER,1);
				$data1 = curl_exec ($Curl_Session1);
				curl_close ($Curl_Session1);
				
				$xml1 = simplexml_load_string($data1);
				
				
				//print_r($xml1);	
				
				if (isset($xml1->basic->reg)) {
					$manufacturer = strtoupper($xml1->basic->make);
					$model = strtoupper($xml1->basic->model);
					$doors = $xml1->basic->doors;
					$date = $xml1->basic->reg_date;
					$year = date('Y',strtotime($date));
				}
				$model_text = "model='$model'";
				$year_text = "year_start<='$year' AND year_end>='$year'";
			}
			else {
				$manufacturer = strtoupper($xml->dvla->vehicle->make);
				$model = strtoupper($xml->mvris->mvris_record->model);
				$model_dvla = strtoupper($xml->dvla->vehicle->model);
				$doors = $xml->mvris->mvris_record->door_count;
				$date = $xml->dvla->vehicle->manufactured_date;
				$year = date('Y',strtotime($date));

				if ($doors=='' || $doors==0) {
					$dvla_doors = strtoupper($xml->dvla->vehicle->body);
					for ($x=1;$x<10;$x++) {
						if (strpos($dvla_doors,$x.' DOOR')!==FALSE) {
							$doors = $x;
							break;
						}
					}
					$model_text = "(model='$model' OR model='$model_dvla')";
					$year_text = "year_start<='$year' AND year_end>='$year'";
				}
			}
		}

		$sql = "SELECT * FROM ggvrmlu_cars_to_products WHERE manufacturer='$manufacturer' AND $model_text AND (doors='$doors' || doors=0 || doors='') AND $year_text";
		//echo '<br>'.$sql;
		$rs = mysqli_query($vrm_lookup_conn,$sql);
		//echo mysqli_error($vrm_lookup_conn);
		//echo ' Rows: '.mysqli_num_rows($rs);
		if ($row = mysqli_fetch_assoc($rs)) {
			$no_rails_1 = stripslashes($row['no_rails_1']);
			$no_rails_2 = stripslashes($row['no_rails_2']);
			$no_rails_3 = stripslashes($row['no_rails_3']);
			$rails_1 = stripslashes($row['rails_1']);
			$rails_2 = stripslashes($row['rails_2']);
			$rails_3 = stripslashes($row['rails_3']);
			$rails_4 = stripslashes($row['rails_4']);
			$rails_5 = stripslashes($row['rails_5']);
			$rails_6 = stripslashes($row['rails_6']);

			if ($no_rails_1!='' || $no_rails_2!='' || $no_rails_3!='' || $rails_1!='' || $rails_2!='' || $rails_3!='' || $rails_4!='' || $rails_5!='' || $rails_6!='') {
			$failed = false;

			echo vrm_display_car_details($manufacturer,$model,$year,$doors);
			?>
				<?php if ($rails_1!='' || $rails_2!='' || $rails_3!='' || $rails_4!='' || $rails_5!='' || $rails_6!=''){?>
					<div class="vc_row">
						<h2 class="vrm-title">If your car has rails</h2>
						<?php
						if ($rails_1!='') echo vrm_get_product($vrm_lookup_conn,$rails_1);
						if ($rails_2!='') echo vrm_get_product($vrm_lookup_conn,$rails_2);
						if ($rails_3!='') echo vrm_get_product($vrm_lookup_conn,$rails_3);
						if ($rails_4!='') echo vrm_get_product($vrm_lookup_conn,$rails_4);
						if ($rails_5!='') echo vrm_get_product($vrm_lookup_conn,$rails_5);
						if ($rails_6!='') echo vrm_get_product($vrm_lookup_conn,$rails_6);
						?>
					</div>
				<?php } ?>
				<?php if ($no_rails_1!='' || $no_rails_2!='' || $no_rails_3!=''){?>
					<div class="vc_row">
						<h2 class="vrm-title">If your car has no rails</h2>
						<p class="vrm-text">You will need to purchase a fixing kit along with your choice of roof bars.</p>
						<?php
						if ($no_rails_1!='') echo vrm_get_product($vrm_lookup_conn,$no_rails_1);
						if ($no_rails_2!='') echo vrm_get_product($vrm_lookup_conn,$no_rails_2);
						if ($no_rails_3!='') echo vrm_get_product($vrm_lookup_conn,$no_rails_3);
						?>
					</div>
				<?php } ?>
				</div>
			<?php
			}
		}
		else {
			//maybe the model is not recognised, so find all models for these details
			$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products WHERE manufacturer='$manufacturer' AND (doors='$doors' || doors=0 || doors='') AND year_start<='$year' AND year_end>='$year'";
			//echo $sql;
			$rs1 = mysqli_query($vrm_lookup_conn,$sql);
			//echo mysqli_error($vrm_lookup_conn);
			if (mysqli_num_rows($rs1)>0) {
				$failed = false;
				?>
				<?php echo vrm_display_car_details($manufacturer,'',$year,$doors); ?>
				<h2 class="vrm-title">Your car model could not be found, please select from the list below</h2>
				<div class="vc_row">
					<?php while ($row1 = mysqli_fetch_assoc($rs1)) { ?>
						<div class="vc_col-lg-3 vc_col-md-4 vc_col-sm-6 vrm_select_model"><a href="/vehicle-lookup/?reg=<?php echo $_GET['reg']?>&vrm_manufacturer=<?php echo $manufacturer?>&vrm_year=<?php echo $year?>&vrm_doors=<?php echo $doors?>&vrm_model=<?php echo $row1['model']?>"><?php echo $row1['model']?></a></div>
					<?php }?>
				</div>
				<?php
			}
			mysqli_free_result($rs1);
		}
		mysqli_free_result($rs);
		mysqli_close($vrm_lookup_conn);

		if ($failed) {
			echo '<p>Unfortunately we could not find your car model in our database.</p>';
		}
	}


	$return = ob_get_clean();
	return $return;
}
add_shortcode('vrmlookup', 'vrm_lookup');

add_action( 'admin_menu', 'ggVRMLookup_admin_menu2' );
function ggVRMLookup_admin_menu2() {
	add_menu_page( 'Towbar Lookup', 'Towbar Lookup', 'manage_options', 'admin-page2', 'ggVRMLookup_admin_page2' );
}

function ggVRMLookup_admin_page2() {
	
	if (isset($_GET['act']) && $_GET['act']=='save') {
		$failed = true;
		
		foreach ($_FILES as $file) {
			$basename = strtolower(basename($file['name']));
			$basename_arr = explode(".",$basename);
			$extension = end($basename_arr);
			$row = 0;
			
			//Fields
			$field_manufacturer = 0;
			$field_model = 1;
			$field_year = 2;
			$field_doors = 3;
			$field_part_number = 4;
			$field_notes = 5;
			$field_steady_plus = 6;
			$field_charging_line = 7;
			$field_further_information = 8;		
			$field_instructions = 9;		
			
			if ($file['tmp_name'] > '') {
				if ($extension=='csv') {
					
					$ggVRMLookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
					if (!$ggVRMLookup_conn) {
					echo 'Unable to connect to DB: ' . mysqli_error();
					exit;
					}
					if (!mysqli_select_db($ggVRMLookup_conn,DB_NAME)) {
					echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
					exit;
					}
					mysqli_query($ggVRMLookup_conn,"SET NAMES utf8");
					
					$sql = "DELETE FROM ggvrmlu_cars_to_products2";
					mysqli_query($ggVRMLookup_conn,$sql);
					
					if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
						while (($data = fgetcsv($handle, 0)) !== FALSE) {
							$row++;
							if ($row>1) {
								$manufacturer = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_manufacturer])));
								$model = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_model])));
								$doors = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_doors]));
								$year = trim($data[$field_year]);
								$part_number = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_part_number]));
								$notes = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_notes]));
								$steady_plus = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_steady_plus]));
								$charging_line = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_charging_line]));
								$further_information = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_further_information]));
								$instructions = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_instructions]));
								
								$year_arr = explode('>',trim($year));
								$year_start = (isset($year_arr[0]) ? $year_arr[0] : '');
								$year_end = (isset($year_arr[1]) ? $year_arr[1] : '');
								
								$year_start = substr($year_start,-2);
								$year_end = substr($year_end,-2);
								
								$year_start = intval(intval($year_start)>80 ? '19'.$year_start : '20'.$year_start);
								if ($year_end=='') $year_end = 9999;
								if ($year_end!=9999) $year_end = intval(intval($year_end)>80 ? '19'.$year_end : '20'.$year_end);
								
								$year_start = ggVRMLookup_safeText($ggVRMLookup_conn,$year_start);
								$year_end = ggVRMLookup_safeText($ggVRMLookup_conn,$year_end);

								if ($doors=='') $doors = 0;
								
								$sql = "INSERT INTO ggvrmlu_cars_to_products2(manufacturer,model,doors,year_start,year_end,part_number,notes,steady_plus,charging_line,further_information,instructions) VALUES ('$manufacturer','$model','$doors','$year_start','$year_end','$part_number','$notes','$steady_plus','$charging_line','$further_information','$instructions')";
								mysqli_query($ggVRMLookup_conn,$sql);
							}
						}
						$failed = false;
					}
					mysqli_close($ggVRMLookup_conn);
				}
			}
		}
		
		if ($failed) echo '<h1>Upload New Towbars Cars-to-Products CSV</h1><p>Import failed.  Please try again.</p>';
		if (!$failed) echo '<h1>Upload Successful</h1><p>Your data has been imported.</p>';
	}
	else {
		?>
		<h1>Upload New Towbars Cars-to-Products CSV</h1>
		<form action="/wp-admin/admin.php?page=admin-page2&act=save" method="post" enctype="multipart/form-data">
			<p>Select your CSV file below to upload and import.  All previous cars-to-products data will be deleted.</p>
			<input type="file" name="file" placeHolder="Select CSV to Upload" /><br /><br />
			<input type="submit" value="Upload" />
		</form>
		<?php
	}
}

function vrm_dropdown_options2() {
	$vrm_lookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
	if (!$vrm_lookup_conn) {
		echo 'Unable to connect to DB: ' . mysqli_error();
		exit;
	}
	if (!mysqli_select_db($vrm_lookup_conn,DB_NAME)) {
		echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
		exit;
	}
	mysqli_query($vrm_lookup_conn,"SET NAMES utf8");

	ob_start();

	//if ($_GET['global']=='graphics') {
	?>
	<form action="/towbars-lookup/" method="post" class="vrm_dropdown_form">
		<h3>Or select your car below...</h3>
		<div class="vc_row vrm_dropdown_options">
			<div class="vc_col-md-3">
				<?php
				$sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products2 ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
				?>
					<select name="vrm_manufacturer" id="vrm_manufacturer">
						<option value="">- Select Make -</option>
						<?php while ($row1 = mysqli_fetch_assoc($rs1)) {?>
							<option value="<?php echo stripslashes($row1['manufacturer'])?>"><?php echo stripslashes($row1['manufacturer'])?></option>
						<?php } ?>
					</select>
				<?php
				}
				mysqli_free_result($rs1);
				?>
			</div>
			<div class="vc_col-md-3">
				<select name="vm__blank" id="vm__blank" class="vrm_models">
					<option value="" class="blank">- Select Model -</option>
				</select>
				<?php $sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products2 ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
					while ($row1 = mysqli_fetch_assoc($rs1)) {
						$manufacturer_field = vrm_safeField(stripslashes($row1['manufacturer']));
						$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products2 WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' ORDER BY model";
						$rs2 = mysqli_query($vrm_lookup_conn,$sql);
						if (mysqli_num_rows($rs2)>0){?>
							<select name="vm_<?php echo $manufacturer_field?>" id="vm_<?php echo $manufacturer_field?>" class="vrm_models">
								<option value="" class="blank">- Select Model -</option>
								<?php while ($row2 = mysqli_fetch_assoc($rs2)) {?>
									<option value="<?php echo stripslashes($row2['model'])?>"><?php echo stripslashes($row2['model'])?></option>
								<?php }?>
							</select>
						<?php }
						mysqli_free_result($rs2);
					}
				}
				mysqli_free_result($rs1);
				?>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('.vrm_models').hide();
					jQuery('#vm__blank').show();

					jQuery('#vrm_manufacturer').on('change',function(){
						jQuery('.vrm_models').hide();
						jQuery('#vm_'+jQuery('#vrm_manufacturer').val().replace(/\(/g,'').replace(/\)/g,'').replace(/\s/g,'_').replace(/\,/g,'_')).show();

						jQuery('.vrm_years').hide();
						jQuery('#vy__blank').show();
						jQuery('.vrm_doors').hide();
						jQuery('#vd__blank').show();
					});
				});
				</script>
			</div>
			<div class="vc_col-md-3">
				<select name="vy__blank" id="vy__blank" class="vrm_years">
					<option value="" class="blank">- Select Year -</option>
				</select>
				<?php
				$sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products2 ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
					while ($row1 = mysqli_fetch_assoc($rs1)) {
						$manufacturer_field = vrm_safeField(stripslashes($row1['manufacturer']));
						$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products2 WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' ORDER BY model";
						$rs2 = mysqli_query($vrm_lookup_conn,$sql);
						if (mysqli_num_rows($rs2)>0){
							while ($row2 = mysqli_fetch_assoc($rs2)) {
								$model_field = vrm_safeField(stripslashes($row2['model']));
								$sql = "SELECT DISTINCT(concat(year_start,' ',year_end)),year_start, year_end FROM ggvrmlu_cars_to_products2 WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' AND model='".mysqli_real_escape_string($vrm_lookup_conn,$row2['model'])."' ORDER BY year_start";
								$rs3 = mysqli_query($vrm_lookup_conn,$sql);
								echo mysqli_error($vrm_lookup_conn);
								if (mysqli_num_rows($rs3)>0){?>
									<select name="vy_<?php echo $manufacturer_field?>_<?php echo $model_field?>" id="vy_<?php echo $manufacturer_field?>_<?php echo $model_field?>" class="vrm_years">
										<option value="" class="blank">- Select Year -</option>
										<?php while ($row3 = mysqli_fetch_assoc($rs3)) {?>
											<option value="<?php echo stripslashes($row3['year_start'])?>"><?php echo stripslashes($row3['year_start'])?> > <?php echo ($row3['year_end']==9999 ? '' : stripslashes($row3['year_end']))?></option>
										<?php }?>
									</select>
								<?php }
								mysqli_free_result($rs3);
							}
						}
						mysqli_free_result($rs2);
					}
				}
				mysqli_free_result($rs1);
				?>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('.vrm_years').hide();
					jQuery('#vy__blank').show();

					jQuery('.vrm_models').on('change',function(){
						jQuery('.vrm_years').hide();
						var manufacturer_field = jQuery('#vrm_manufacturer').val().replace(/\(/g,'').replace(/\)/g,'').replace(/\,/g,'_').replace(/ /g,'_');
						var model_field = jQuery('#vm_'+manufacturer_field).val().replace(/\(/g,'').replace(/\)/g,'').replace(/\,/g,'_').replace(/ /g,'_');
						jQuery('#vy_'+manufacturer_field+'_'+model_field).show();

						jQuery('.vrm_doors').hide();
						jQuery('#vd__blank').show();
					});
				});
				</script>
			</div>
			<div class="vc_col-md-3">
				<select name="vd__blank" id="vd__blank" class="vrm_doors">
					<option value="" class="blank">- Select Doors -</option>
				</select>
				<?php
				$sql = "SELECT DISTINCT(manufacturer) FROM ggvrmlu_cars_to_products2 ORDER BY manufacturer";
				$rs1 = mysqli_query($vrm_lookup_conn,$sql);
				if (mysqli_num_rows($rs1)>0){
					while ($row1 = mysqli_fetch_assoc($rs1)) {
						$manufacturer_field = vrm_safeField(stripslashes($row1['manufacturer']));
						$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products2 WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' ORDER BY model";
						$rs2 = mysqli_query($vrm_lookup_conn,$sql);
						if (mysqli_num_rows($rs2)>0){
							while ($row2 = mysqli_fetch_assoc($rs2)) {
								$model_field = vrm_safeField(stripslashes($row2['model']));
								$sql = "SELECT DISTINCT(concat(year_start,' ',year_end)),year_start, year_end FROM ggvrmlu_cars_to_products2 WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' AND model='".mysqli_real_escape_string($vrm_lookup_conn,$row2['model'])."' ORDER BY year_start";
								$rs3 = mysqli_query($vrm_lookup_conn,$sql);
								echo mysqli_error($vrm_lookup_conn);
								if (mysqli_num_rows($rs3)>0){
									while ($row3 = mysqli_fetch_assoc($rs3)) {
										$year_start = vrm_safeField(stripslashes($row3['year_start']));
										$sql = "SELECT DISTINCT(doors) FROM ggvrmlu_cars_to_products2 WHERE manufacturer='".mysqli_real_escape_string($vrm_lookup_conn,$row1['manufacturer'])."' AND model='".mysqli_real_escape_string($vrm_lookup_conn,$row2['model'])."' AND year_start='".mysqli_real_escape_string($vrm_lookup_conn,$row3['year_start'])."' ORDER BY doors";
										$rs4 = mysqli_query($vrm_lookup_conn,$sql);
										if (mysqli_num_rows($rs4)>0){?>
											<select name="vd_<?php echo $manufacturer_field?>_<?php echo $model_field?>_<?php echo $year_start?>" id="vd_<?php echo $manufacturer_field?>_<?php echo $model_field?>_<?php echo $year_start?>" class="vrm_doors">
												<option value="" class="blank">- Select Doors -</option>
												<?php while ($row4 = mysqli_fetch_assoc($rs4)) {?>
													<option value="<?php echo stripslashes($row4['doors'])?>"><?php echo stripslashes($row4['doors'])?></option>
												<?php }?>
											</select>
										<?php }
										mysqli_free_result($rs4);
									}
								}
								mysqli_free_result($rs3);
							}
						}
						mysqli_free_result($rs2);
					}
				}
				mysqli_free_result($rs1);
				?>
				<button type="submit">Go</button>
				<script type="text/javascript">
				jQuery(document).ready(function(){
					jQuery('.vrm_doors').hide();
					jQuery('#vd__blank').show();

					jQuery('.vrm_years').on('change',function(){
						jQuery('.vrm_doors').hide();
						var manufacturer_field = jQuery('#vrm_manufacturer').val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_').replace(/\,/g,'_');
						var model_field = jQuery('#vm_'+manufacturer_field).val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_').replace(/\,/g,'_');
						var year_field = jQuery('#vy_'+manufacturer_field+'_'+model_field).val().replace(/\(/g,'').replace(/\)/g,'').replace(/ /g,'_').replace(/\,/g,'_');
						jQuery('#vd_'+manufacturer_field+'_'+model_field+'_'+year_field).show();
					});
				});
				</script>
			</div>
		</div>
	</form>
	<?php
	$return = ob_get_clean();
	return $return;
}
add_shortcode('vrmdropdownoptions2', 'vrm_dropdown_options2');

function vrm_lookup_form2() {
	ob_start();
	?>
	<div class="vrm-lookup-form">
		<h2>Enter Your Car Registration Number</h2>
		<?php /*<p>Enter your vehicle registration number to find which of our roof bars will fit your car.</p> */ ?>
		<form action="/towbars-lookup/" method="get">
			<input type="text" name="reg" placeHolder="AA11 ZZZ" value="<?php echo (isset($_GET['reg']) ? strtoupper($_GET['reg']) : '')?>" /><button type="submit">Go</button>
		</form>
	</div>
	<?php
	$return = ob_get_clean();
	return $return;
}
add_shortcode('vrmlookupform2', 'vrm_lookup_form2');

function vrm_lookup2() {
	ob_start();

	$failed = true;

	if (isset($_GET['reg']) || isset($_POST['vrm_manufacturer'])) {

		$vrm_lookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
		if (!$vrm_lookup_conn) {
			echo 'Unable to connect to DB: ' . mysqli_error();
			exit;
		}
		if (!mysqli_select_db($vrm_lookup_conn,DB_NAME)) {
			echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
			exit;
		}
		mysqli_query($vrm_lookup_conn,"SET NAMES utf8");

		$year_text = '';

		if (isset($_GET['vrm_manufacturer']) && isset($_GET['vrm_model']) && isset($_GET['vrm_year']) && isset($_GET['vrm_doors'])) {
			$manufacturer = vrm_safeText($vrm_lookup_conn,$_GET['vrm_manufacturer']);
			$model = vrm_safeText($vrm_lookup_conn,$_GET['vrm_model']);
			$year = vrm_safeText($vrm_lookup_conn,$_GET['vrm_year']);
			$doors = vrm_safeText($vrm_lookup_conn,$_GET['vrm_doors']);

			$model_text = "model='$model'";
			if (isset($_GET['reg'])) $year_text = "year_start<='$year' AND year_end>='$year'";
			else $year_text = "year_start='$year'";	

		}
		else if (isset($_POST['vrm_manufacturer'])) {
			$manufacturer = vrm_safeText($vrm_lookup_conn,$_POST['vrm_manufacturer']);
			$manufacturer_field = vrm_safeField($manufacturer);
			$model = vrm_safeText($vrm_lookup_conn,$_POST['vm_'.$manufacturer_field]);
			$model_field = vrm_safeField($model);
			$year = vrm_safeText($vrm_lookup_conn,$_POST['vy_'.$manufacturer_field.'_'.$model_field]);
			$year_field = vrm_safeField($year);
			$doors = vrm_safeText($vrm_lookup_conn,$_POST['vd_'.$manufacturer_field.'_'.$model_field.'_'.$year_field]);
			$doors_field = vrm_safeField($doors);

			$model_text = "model='$model'";
			$year_text = "year_start='$year'";
		}
		else {
			$reg = $_GET['reg'];
			$feedname = 'MAYPOLE';
			$username = 'MAYPOLE';
			$password = urlencode("RG'TwNMxF7VYd0VJ2,r*Ok<;5*DEF.pk7zQ_ZVUf");

			$postfields = "vrm=$reg&mileage=&feedName=$feedname&versionNumber=2&userName=$username&password=$password";

			$Curl_Session = curl_init('https://jarvis.cdlis.co.uk/jarvis-webapp/search');
			curl_setopt($Curl_Session, CURLOPT_RETURNTRANSFER,1);
			curl_setopt ($Curl_Session, CURLOPT_POST, 1);
			curl_setopt ($Curl_Session, CURLOPT_POSTFIELDS, $postfields);
			$data = curl_exec ($Curl_Session);
			curl_close ($Curl_Session);

			$xml = simplexml_load_string($data);

			//print_r($xml);
			
			if ((isset($xml->code) && $xml->code=='404') || strlen($data)==0) {
				//do an irish plate lookup
				$newreg = strtoupper(str_replace(' ','',$_GET['reg']));
				$Curl_Session1 = curl_init('https://api.motorcheck.ie/vehicle/reg/'.$newreg.'/basic?_username=maypole-ltd&_api_key=32f9c0dabfc545bb93c78df89c7e696d96cd5d61');
				curl_setopt($Curl_Session1, CURLOPT_RETURNTRANSFER,1);
				$data1 = curl_exec ($Curl_Session1);
				curl_close ($Curl_Session1);
				
				$xml1 = simplexml_load_string($data1);

				//print_r($xml1);
				
				if (isset($xml1->basic->reg)) {
					$manufacturer = strtoupper($xml1->basic->make);
					$model = strtoupper($xml1->basic->model);
					$doors = $xml1->basic->doors;
					$date = $xml1->basic->reg_date;
					$year = date('Y',strtotime($date));
				}
				$model_text = "model='$model'";
				$year_text = "year_start<='$year' AND year_end>='$year'";
			}
			else {

				$manufacturer = strtoupper($xml->dvla->vehicle->make);
				$model = strtoupper($xml->mvris->mvris_record->model);
				$model_dvla = strtoupper($xml->dvla->vehicle->model);
				$doors = $xml->mvris->mvris_record->door_count;
				$date = $xml->dvla->vehicle->manufactured_date;
				$year = date('Y',strtotime($date));

				if ($doors=='') {
					$dvla_doors = strtoupper($xml->dvla->vehicle->body);
					for ($x=1;$x<10;$x++) {
						if (strpos($dvla_doors,$x.' DOOR')!==FALSE) {
							$doors = $x;
							break;
						}
					}
				}

				$model_text = "(model='$model' OR model='$model_dvla')";
				$year_text = "year_start<='$year' AND year_end>='$year'";
			}
		}

		$sql = "SELECT * FROM ggvrmlu_cars_to_products2 WHERE manufacturer='$manufacturer' AND $model_text AND (doors='$doors' || doors=0) AND $year_text";
		//echo '<br>'.$sql;
		$rs = mysqli_query($vrm_lookup_conn,$sql);
		//echo mysqli_error($vrm_lookup_conn);
		//echo ' Rows: '.mysqli_num_rows($rs);
		if (mysqli_num_rows($rs)>0) {
			$failed = false;
			echo vrm_display_car_details($manufacturer,$model,$year,$doors);
			
			while ($row = mysqli_fetch_assoc($rs)) {
				$part_number = stripslashes($row['part_number']);
				$notes = stripslashes($row['notes']);
				$steady_plus = stripslashes($row['steady_plus']);
				$charging_line = stripslashes($row['charging_line']);
				$further_information = stripslashes($row['further_information']);
				$instructions = stripslashes($row['instructions']);
				
				$steady_plus_url = '';
				if ($steady_plus!='') {
					$steady_plus_post_id = vrm_get_postid($vrm_lookup_conn,'_sku',$steady_plus);
					$steady_plus_url = '/product/'.vrm_get_postfield($vrm_lookup_conn,$steady_plus_post_id,'post_name').'/';
				}
				$charging_line_url = '';
				if ($charging_line!='') {
					$charging_line_post_id = vrm_get_postid($vrm_lookup_conn,'_sku',$charging_line);
					$charging_line_url = '/product/'.vrm_get_postfield($vrm_lookup_conn,$charging_line_post_id,'post_name').'/';
				}
				
				$post_id = vrm_get_postid($vrm_lookup_conn,'_sku',$part_number);
				$title = vrm_get_postfield($vrm_lookup_conn,$post_id,'post_title');
				$status = vrm_get_postfield($vrm_lookup_conn,$post_id,'post_status');
				$url = '/product/'.vrm_get_postfield($vrm_lookup_conn,$post_id,'post_name').'/';
				$price = vrm_get_metafield($vrm_lookup_conn,$post_id,'_price');
				$thumbnail = vrm_get_metafield($vrm_lookup_conn,$post_id,'_thumbnail_id');
				
				//echo '<br>part_number: '.$part_number;
				//echo '<br>post_id: '.$post_id;

				if ($part_number!='' && $post_id!='') {
					$poles = '';
					$time = '';
					$flasher = '';
					$activation = '';
					$park_distance = '';
					$led_compatible = '';
					$socket = '';
					$connection = '';
					$steady_plus = '';
					//$charging_line = '';
					//$further_information = '';
					//$instructions = '';
					
					$show_attributes = false;
					$sql = "SELECT * FROM ggvrmlu_product_attributes WHERE part_number='$part_number'";
					$rsa = mysqli_query($vrm_lookup_conn,$sql);
					if ($rowa = mysqli_fetch_assoc($rsa)) {
						$show_attributes = true;
						$poles = stripslashes($rowa['poles']);
						$time = stripslashes($rowa['time']);
						$flasher = stripslashes($rowa['flasher']);
						$activation = stripslashes($rowa['activation']);
						$park_distance = stripslashes($rowa['park_distance']);
						$led_compatible = stripslashes($rowa['led_compatible']);
						$socket = stripslashes($rowa['socket']);
						$connection = stripslashes($rowa['connection']);
						$steady_plus = stripslashes($rowa['steady_plus']);
						$charging_line = stripslashes($rowa['charging_line']);
						if ($further_information=='') $further_information = stripslashes($rowa['further_information']);
						//$instructions = stripslashes($rowa['instructions']);
					}
					mysqli_free_result($rsa);
					?>
							<div class="tb_wrapper">
								<?php $image = wp_get_attachment_image_src($thumbnail,'single-post-thumbnail') ?>
								<a href="<?php echo $url?>" class="product_img"><img src="<?php echo $image[0]; ?>" data-id="<?php echo $post_id; ?>"></a>
								<div class="tb_content">
									<h3 class="title"><a href="<?php echo $url?>"><?php echo $title?></a></h3>
									<?php if ($notes!=''):?><p><?php echo $notes?></p><?php endif;?>
									<?php if ($show_attributes):?>
									<table class="attributes">
										<thead>
											<tr>
												<th width="20%">No. of Poles <i class="fa fa-bolt"></i></th>
												<th width="20%">Installation Time In Mins (ca.) <i class="fa fa-hourglass-start"></i></th>
												<th width="20%">Flasher Monitoring <i class="fa fa-bolt"></i></th>
												<th width="20%">Activation Required <i class="fa fa-check-circle"></i></th>
												<th width="20%">Deactivation Park Distance Control <i class="fa fa-check-circle"></i></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td><?php echo $poles?></td>
												<td><?php echo $time?></td>
												<td><?php echo $flasher?></td>
												<td><?php echo $activation?></td>
												<td><?php echo $park_distance?></td>
											</tr>
										</tbody>
									</table>
									<table class="attributes">
										<thead>
											<tr>
												<th width="20%">LED Compatible <i class="fa fa-adjust"></i></th>
												<th width="20%">Socket Connection <i class="fa fa-plug"></i></th>
												<th width="20%">Connection to Vehicle Electrical System <i class="fa fa-link"></i></th>
												<th width="20%">Steady Plus <i class="fa fa-plus"></i></th>
												<th width="20%">Charging Line <i class="fa fa-plug"></i></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td><?php echo $led_compatible?></td>
												<td><?php echo $socket?></td>
												<td><?php echo $connection?></td>
												<td><?php if ($steady_plus=='' || $steady_plus=='No'):?>No<?php elseif ($steady_plus=='Included'):?>Included<?php else:?>Additional <a href="<?php echo $steady_plus_url?>"><?php echo $steady_plus?></a><?php endif;?></td>
												<td><?php if ($charging_line=='' || $charging_line=='No'):?>No<?php else: ?>Additional <a href="<?php echo $charging_line_url?>"><?php echo $charging_line?></a><?php endif;?></td>
											</tr>
										</tbody>
									</table>
									<?php endif;?>
									<?php if ($further_information!=''):?><p><?php echo $further_information?></p><?php endif;?>
									<?php if ($instructions!=''):?><a href="<?php echo $instructions?>" class="button instructions" target="_blank">Instructions</a><?php endif;?>
									<a href="<?php echo $url?>" class="button more">View More</a>
								</div>
								<div class="clear"></div>
							</div>
					<?php
				}
			}
		}
		else {
			//maybe the model is not recognised, so find all models for these details
			$sql = "SELECT DISTINCT(model) FROM ggvrmlu_cars_to_products2 WHERE manufacturer='$manufacturer' AND (doors='$doors' || doors=0 || doors='') AND year_start<='$year' AND year_end>='$year'";
			//echo $sql;
			$rs1 = mysqli_query($vrm_lookup_conn,$sql);
			//echo mysqli_error($vrm_lookup_conn);
			if (mysqli_num_rows($rs1)>0) {
				$failed = false;
				?>
				<?php echo vrm_display_car_details($manufacturer,'',$year,$doors); ?>
				<h2 class="vrm-title">Your car model could not be found, please select from the list below</h2>
				<div class="vc_row">
					<?php while ($row1 = mysqli_fetch_assoc($rs1)) { ?>
						<div class="vc_col-lg-3 vc_col-md-4 vc_col-sm-6 vrm_select_model"><a href="/towbars-lookup/?reg=<?php echo $_GET['reg']?>&vrm_manufacturer=<?php echo $manufacturer?>&vrm_year=<?php echo $year?>&vrm_doors=<?php echo $doors?>&vrm_model=<?php echo $row1['model']?>"><?php echo $row1['model']?></a></div>
					<?php } ?>
				</div>
				<?php
			}
			mysqli_free_result($rs1);
		}
		mysqli_free_result($rs);
		mysqli_close($vrm_lookup_conn);

		if ($failed) {
			echo '<p>Unfortunately we could not find your car model in our database.</p>';
		}
	}


	$return = ob_get_clean();
	return $return;
}
add_shortcode('vrmlookup2', 'vrm_lookup2');


add_action( 'admin_menu', 'ggVRMLookup_admin_menu3' );
function ggVRMLookup_admin_menu3() {
	add_menu_page( 'Towbar Attributes', 'Towbar Attributes', 'manage_options', 'admin-page3', 'ggVRMLookup_admin_page3' );
}

function ggVRMLookup_admin_page3() {
	
	if (isset($_GET['act']) && $_GET['act']=='save') {
		$failed = true;
		
		foreach ($_FILES as $file) {
			$basename = strtolower(basename($file['name']));
			$basename_arr = explode(".",$basename);
			$extension = end($basename_arr);
			$row = 0;
			
			//Fields
			$field_part_number = 0;
			$field_barcode = 1;
			$field_poles = 2;
			$field_time = 3;
			$field_flasher = 4;
			$field_activation = 5;
			$field_park_distance = 6;
			$field_led_compatible = 7;
			$field_socket = 8;
			$field_connection = 9;
			$field_steady_plus = 10;
			$field_charging_line = 11;
			$field_further_information = 12;
			$field_instructions = 13;
			
			if ($file['tmp_name'] > '') {
				if ($extension=='csv') {
					
					$ggVRMLookup_conn = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD);
					if (!$ggVRMLookup_conn) {
					echo 'Unable to connect to DB: ' . mysqli_error();
					exit;
					}
					if (!mysqli_select_db($ggVRMLookup_conn,DB_NAME)) {
					echo 'Unable to select '.DB_NAME.': ' . mysqli_error();
					exit;
					}
					mysqli_query($ggVRMLookup_conn,"SET NAMES utf8");
					
					$sql = "DELETE FROM ggvrmlu_product_attributes";
					mysqli_query($ggVRMLookup_conn,$sql);
					
					if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
						while (($data = fgetcsv($handle, 0)) !== FALSE) {
							$row++;
							if ($row>1) {
								$part_number = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_part_number])));
								$barcode = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_barcode])));
								$poles = trim(strtoupper(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_poles])));
								$time = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_time]));
								$flasher = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_flasher]));
								$activation = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_activation]));
								$park_distance = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_park_distance]));
								$led_compatible = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_led_compatible]));
								$socket = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_socket]));
								$connection = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_connection]));
								$steady_plus = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_steady_plus]));
								$charging_line = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_charging_line]));
								$further_information = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_further_information]));
								$instructions = trim(ggVRMLookup_safeText($ggVRMLookup_conn,$data[$field_instructions]));
								
								$sql = "INSERT INTO ggvrmlu_product_attributes(part_number,barcode,poles,time,flasher,activation,park_distance,led_compatible,socket,connection,steady_plus,charging_line,further_information,instructions) VALUES ('$part_number','$barcode','$poles','$time','$flasher','$activation','$park_distance','$led_compatible','$socket','$connection','$steady_plus','$charging_line','$further_information','$instructions')";
								//echo '<br>'.$sql;
								mysqli_query($ggVRMLookup_conn,$sql);
								//echo mysqli_error($ggVRMLookup_conn);
								
							}
						}
						$failed = false;
					}
					mysqli_close($ggVRMLookup_conn);
				}
			}
		}
		
		if ($failed) echo '<h1>Upload New Towbar Product Attributes CSV</h1><p>Import failed.  Please try again.</p>';
		if (!$failed) echo '<h1>Upload Successful</h1><p>Your data has been imported.</p>';
	}
	else {
		?>
		<h1>Upload New Towbars Product Attributes CSV</h1>
		<form action="/wp-admin/admin.php?page=admin-page3&act=save" method="post" enctype="multipart/form-data">
			<p>Select your CSV file below to upload and import.  All previous product-attributes data will be deleted.</p>
			<input type="file" name="file" placeHolder="Select CSV to Upload" /><br /><br />
			<input type="submit" value="Upload" />
		</form>
		<?php
	}
}

/*
function ggVRMLookup_Activate() {
	//flush_rewrite_rules();
}

function ggVRMLookup_Deactivate() {
	
}

//Activation
register_activation_hook(__FILE__, 'ggVRMLookup_Activate');
register_deactivation_hook(__FILE__, 'ggVRMLookup_Deactivate');
*/
