<?php 

/* 
Template Name: Booking
*/

// Fetch options stored in $data
global $data;


/**
 * Sends mail by SMTP or mail(), depending on options
 *
 * @global array $data
 * @param array $from array('name' => string, 'email' => string)
 * @param array $to array('name' => string, 'email' => string)
 * @param array $replyto array('name' => string, 'email' => string)
 * @param string $subject
 * @param string $body
 */
if (!function_exists('qns_mail')) {
function qns_mail($from, $to, $replyto, $subject, $body) {
	// import options
	global $data;
	// determine whether to send by SMTP
	if (!empty($data['enable_smtp']) && !empty($data['smtp_host']) && !empty($data['smtp_port'])) {
	// convert encryption
	$smtp_encryption = '';
	switch($data['smtp_encryption']) {
		case 'No encryption': $smtp_encryption = ''; break;
		case 'SSL encryption': $smtp_encryption = 'ssl'; break;
		case 'TLS encryption': $smtp_encryption = 'tls'; break;
		default: $smtp_encryption = '';
	}
	// instantiate mailer
	global $phpmailer;
	if (!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
	}
	$phpmailer = new PHPMailer(true); // clear out any previous settings
	$phpmailer->isSMTP();
	$phpmailer->Host = $data['smtp_host'];
	$phpmailer->Port = $data['smtp_port'];
	$phpmailer->SMTPSecure = $smtp_encryption;
	// determine whether to send with authentication
	if (!empty($data['smtp_auth']) && !empty($data['smtp_username'])) {
		$phpmailer->SMTPAuth = TRUE;
		$phpmailer->Username = $data['smtp_username'];
		$phpmailer->Password = $data['smtp_password'];
	} else {
		$phpmailer->SMTPAuth = FALSE;
	}
	$phpmailer->From = $from['email'];
	$phpmailer->FromName = $from['name'];
	$phpmailer->clearAllRecipients();
	$phpmailer->AddAddress($to['email']);
	$phpmailer->AddReplyTo($replyto['email']);
	$phpmailer->Subject = $subject;
	$phpmailer->CharSet = 'UTF-8';
	$phpmailer->Body = $body;
	$phpmailer->IsHTML(TRUE);

	// send
	try {
		$phpmailer->Send();
	} catch (Exception $ex) {
		wp_die($ex->getMessage());
	}
	unset($phpmailer);
	} else {
		$headers = <<<EOT
MIME-Version: 1.0
Content-type: text/html; charset=UTF-8
From: "{$from['name']}" <{$from['email']}>
Reply-To: {$replyto['email']}
EOT;
		mail($to['email'], $subject, $body, $headers);
	}
} // end function
} // end if

// Get booking page ID
$booking_page = $data['booking_page_url'];

// Check if form has been submit
if ( $_SERVER['HTTP_REFERER'] == $data['booking_page_url']  && $_POST['submit'] == 'submit' ) {
	$submit = true;
	
	// Post form data from this page
	$book_room_type = $_POST['book_room_type'];
	$book_date_from = $_POST['book_date_from'];
	$book_date_to = $_POST['book_date_to'];
	$book_full_name = $_POST['book_full_name'];
	$book_num_people = $_POST['book_num_people'];
	$book_email = $_POST['book_email'];
	$book_phone = $_POST['book_phone'];
	$book_message = $_POST['book_message'];
	$book_room_price = $_POST['book_room_price'];
	
	if(trim( $book_room_type ) === '') {
		$book_room_type_error = __('Room Type is a required field', 'qns');
		$got_error = true;
	}
	
	if(trim( $book_date_from ) === '') {
		$book_date_from_error = __('Date From is a required field', 'qns');
		$got_error = true;
	}
	
	if(trim( $book_date_to ) === '') {
		$book_date_to_error = __('Date To is a required field', 'qns');
		$got_error = true;
	}
	
	if(trim( $book_full_name ) === '' or trim( $book_full_name ) === __('Full Name','qns') ) {
		$book_full_name_error = __('Full Name is a required field', 'qns');
		$got_error = true;
	}
	
	if(trim( $book_num_people ) === '' or trim( $book_num_people ) === __('Number of People','qns') ) {
		$book_num_people_error = __('Number of People is a required field', 'qns');
		$got_error = true;
	}
	
	if(trim( $book_email ) === '' or trim( $book_email ) === __('Email Address','qns') or valid_email( trim($book_email) ) === FALSE ) {
		$book_email_error = __('Email Address is a required field', 'qns');
		$got_error = true;
	}
	
}

// If the form on this page has not been submit post the values from the accommodation page
else {
	
	if($_POST['book_room_type_and_price'] != '') {
		$book_room_price_array = explode(',', $_POST['book_room_type_and_price']);		
		$book_room_type = $book_room_price_array[0];
		$book_room_price = $book_room_price_array[1];
	}
	
	else {
		
		$book_room_type = $_POST['book_room_type'];
		$book_room_price = $_POST['book_room_price'];
		
	}
	
	$book_date_from = $_POST['book_date_from'];
	$book_date_to = $_POST['book_date_to'];
	
}

// Calculate Length of Stay
if( $data['datepickerformat'] == 'mm/dd/yyyy') {
	$date_from_stt = strtotime($book_date_from);
	$date_to_stt = strtotime($book_date_to);
	$datediff = $date_to_stt - $date_from_stt;
	$total_stay = floor($datediff/(60*60*24));
} else {
	function bookDateDiff($date1,$date2) {

		$old_from = $date1;
		$arr1 = explode('/', $old_from);
		$book_date_from = $arr1[1].'/'.$arr1[0].'/'.$arr1[2];

		$old_to = $date2;
		$arr2 = explode('/', $old_to);
		$book_date_to = $arr2[1].'/'.$arr2[0].'/'.$arr2[2];

		$date_from_stt = strtotime($book_date_from);
		$date_to_stt = strtotime($book_date_to);
		$datediff = $date_to_stt - $date_from_stt;
		$total_stay = floor($datediff/(60*60*24));

		return $total_stay;

	}

	$total_stay = bookDateDiff($book_date_from,$book_date_to);
}



// Calculate Total Price
$total_price = $book_room_price * $total_stay;

// Get currency unit stored in the theme options
if ( $data['currency_unit'] !== '' ) {
	$currency_unit = $data['currency_unit'];
}

// If the unit is not set
else {
	$currency_unit = '$';
}

// If form has been submit and there are no errors send it
if ( $submit == true && $got_error != true ) {
	
	$email_to = $data['contact_email'];

	if (!isset($email_to) || ($email_to == '') ){
		$email_to = get_option('admin_email');
	}
	
	// Send email to website admin
	$subject = htmlspecialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . __(' Booking Form','qns');
	
	$body = '<ul style="margin: 0px;padding:0 0 0 15px;">';
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Room Type','qns') . ":</strong> " . $book_room_type . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Date','qns') . ":</strong> " . $book_date_from . " - " . $book_date_to . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Guest Name','qns') . ":</strong> " . $book_full_name . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Number of People','qns') . ":</strong> " . $book_num_people . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Email','qns') . ":</strong> " . $book_email . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Phone','qns') . ":</strong> " . $book_phone . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Price Quoted','qns') . ":</strong> " . $currency_unit.$total_price . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('Special Requirements','qns') . ":</strong> " . $book_message . "</li>";
	$body .= '<li style="margin-bottom: 3px;"><strong>' . __('IP Address','qns') . ":</strong> " . $_SERVER['REMOTE_ADDR'] . "</li>";
	$body .= '</ul>';

	qns_mail(
		array('name' => $book_full_name, 'email' => $email_to), // from
		array('name' => '', 'email' => $email_to), // to
		array('name' => '', 'email' => $book_email), // replyto
		$subject,
		$body
	);
	$emailSent = true;
	
	// Send email to guest
	$subject2 = htmlspecialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ': ' . __('Reservation Received','qns');
	
	if($data['accom_success_msg']) {
		$body2 = $data['accom_success_msg'] . '<br /><br />';
	} else {
		$body2 = __('Booking Successful! Nice Hotel will reply within 24 hours <br /><br />','qns');
	}
	
	$body2 .= '<ul style="margin: 0px;padding:0 0 0 15px;">';
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Room Type','qns') . ":</strong> " . $book_room_type . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Date','qns') . ":</strong> " . $book_date_from . " - " . $book_date_to . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Guest Name','qns') . ":</strong> " . $book_full_name . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Number of People','qns') . ":</strong> " . $book_num_people . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Email','qns') . ":</strong> " . $book_email . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Phone','qns') . ":</strong> " . $book_phone . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Price Quoted','qns') . ":</strong> " . $currency_unit.$total_price . "</li>";
	$body2 .= '<li style="margin-bottom: 3px;"><strong>' . __('Special Requirements','qns') . ":</strong> " . $book_message . "</li>";
	$body2 .= '</ul>';

	qns_mail(
		array('name' => str_replace('"', '\"', htmlspecialchars_decode(get_bloginfo('name'), ENT_QUOTES)), 'email' => $email_to), // from
		array('name' => '', 'email' => $book_email), // to
		array('name' => '', 'email' => $email_to), // replyto
		$subject2,
		$body2
	);
	$emailSent = true;
	
}

?>

<?php get_header(); ?>

	<?php //Display Page Header
		global $wp_query;
		$postid = $wp_query->post->ID;
		echo page_header( get_post_meta($postid, 'qns_page_header_image', true) );
		wp_reset_query();
	?>
	
	<!-- BEGIN .section -->
	<div class="section page-full clearfix">

		<h2 class="page-title"><?php _e('Room Booking','qns'); ?></h2>
		
		<!-- BEGIN .page-content -->
		<div class="page-content page-content-full">
			
			<?php // Prevent users from loading the page directly
				if ( $_SERVER['HTTP_REFERER'] == '' or $_POST['book_confirm'] != '1' ) : 
					echo '<div class="msg fail"><p>' . __('Please do not load this page directly, go to the accommodation page first and select room','qns') . '</p></div>'; 
			?>
			
			<?php else : ?>
			
			<!-- BEGIN .even-cols -->
			<div class="even-cols booking-cols clearfix">
				
				<!-- BEGIN .one-half -->
				<div class="one-half">
					
					<?php
					
						if ( $submit == true && $got_error != true ) {
							echo '<div class="msg success"><p>';
							
							if($data['accom_success_msg']) {
								echo $data['accom_success_msg'];
							} else {
								_e('Booking Successful! Nice Hotel will reply within 24 hours','qns');
							}
							
							echo '</p></div>';
						}
						
						if ( $got_error == true ) {
							
							echo '<div class="msg fail">
							<ul class="list-fail">';
							
							if ( $book_room_type_error != '' ) { echo '<li>' . $book_room_type_error . '</li>'; }
							if ( $book_date_from_error != '' ) { echo '<li>' . $book_date_from_error . '</li>'; }
							if ( $book_date_to_error != '' ) { echo '<li>' . $book_date_to_error . '</li>'; }
							if ( $book_full_name_error != '' ) { echo '<li>' . $book_full_name_error . '</li>'; }
							if ( $book_num_people_error != '' ) { echo '<li>' . $book_num_people_error . '</li>'; }
							if ( $book_email_error != '' ) { echo '<li>' . $book_email_error . '</li>'; }

							echo '</ul></div>';
							
						}
					
					?>
					
					<?php if ( $emailSent == true ) : ?>
						
						<table class="booking-table">
							<thead>
								<tr>
									<th><?php _e('Booking Option','qns'); ?></th>
									<th><?php _e('Selection','qns'); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong><?php _e('Room Type','qns'); ?>:</strong></td>
									<td><?php echo $book_room_type; ?></td>
								</tr>
								<tr>
									<td><strong><?php _e('Date','qns'); ?>:</strong></td>
									<td><?php echo $book_date_from; ?> - <?php echo $book_date_to; ?> (<?php echo $total_stay; ?> <?php _e('Nights','qns'); ?>)</td>
								</tr>
								<tr>
									<td><strong><?php _e('Name','qns'); ?>:</strong></td>
									<td><?php echo $book_full_name; ?></td>
								</tr>
								<tr>
									<td><strong><?php _e('Number of People','qns'); ?>:</strong></td>
									<td><?php echo $book_num_people; ?></td>
								</tr>
								<tr>
									<td><strong><?php _e('Email Address','qns'); ?>:</strong></td>
									<td><?php echo $book_email; ?></td>
								</tr>
								<tr>
									<td><strong><?php _e('Phone Number','qns'); ?>:</strong></td>
									<td><?php echo $book_phone; ?></td>
								</tr>
								<tr>
									<td><strong><?php _e('Special Requirements','qns'); ?>:</strong></td>
									<td><?php echo $book_message; ?></td>
								</tr>
								<tr class="table-highlight">
									<td><strong><?php _e('Total Cost','qns'); ?>:</strong></td>
									<td><?php echo $currency_unit.$total_price; ?></td>
								</tr>
							</tbody>
						</table>
					
					<?php else : ?>
					
					<!-- BEGIN .booknow-accompage -->
					<div class="booknow-accompage full-booking-form">
						
						<div class="book-price">
							
							<h2 class="price"><?php echo $currency_unit; ?><span class="room-price"><?php echo $total_price; ?></span><span class="price-detail"><span class="price-detail-value"><?php echo $total_stay; ?></span> <?php _e('Nights','qns'); ?></span></h2>
							
							<div class="price-tl"></div>
							<div class="price-tr"></div>
							<div class="price-bl"></div>
							<div class="price-br"></div>
							
						</div>
						
						<script>
							// Set room price for external JS file
							var getPrice = <?php echo $book_room_price; ?>;
						</script>
						
						<form class="booking-form booking-form-accompage" name="bookroom" action="<?php echo $booking_page; ?>" method="post">
							
							<input type="text" value="<?php echo $book_room_type; ?>" class="text-input" disabled="disabled">

							<div class="clearfix">
								<input type="text" name="book_date_from" id="datefrom" value="<?php echo $book_date_from; ?>" class="input-half datepicker">
								<input type="text" name="book_date_to" id="dateto" value="<?php echo $book_date_to; ?>" class="input-half input-half-last datepicker">
							</div>
							
							<input type="text" onblur="if(this.value=='')this.value='<?php _e('Full Name','qns'); ?>';" onfocus="if(this.value=='<?php _e('Full Name','qns'); ?>')this.value='';" value="<?php 
								if(isset($_POST['book_full_name'])) : echo $_POST['book_full_name']; 
								else : _e('Full Name','qns'); 
								endif;
							?>" name="book_full_name" class="text-input" />
							
							<input type="text" onblur="if(this.value=='')this.value='<?php _e('Number of People','qns'); ?>';" onfocus="if(this.value=='<?php _e('Number of People','qns'); ?>')this.value='';" value="<?php 
								if(isset($_POST['book_num_people'])) : echo $_POST['book_num_people']; 
								else : _e('Number of People','qns'); 
								endif;
							?>" name="book_num_people" class="text-input" />
							
							<input type="text" onblur="if(this.value=='')this.value='<?php _e('Email Address','qns'); ?>';" onfocus="if(this.value=='<?php _e('Email Address','qns'); ?>')this.value='';" value="<?php 
								if(isset($_POST['book_email'])) : echo $_POST['book_email']; 
								else : _e('Email Address','qns'); 
								endif;
							?>" name="book_email" class="text-input" />
							
							<input type="text" onblur="if(this.value=='')this.value='<?php _e('Phone Number','qns'); ?>';" onfocus="if(this.value=='<?php _e('Phone Number','qns'); ?>')this.value='';" value="<?php 
								if(isset($_POST['book_phone'])) : echo $_POST['book_phone']; 
								else : _e('Phone Number','qns'); 
								endif;
							?>" name="book_phone" class="text-input" />
							
							<textarea class="text-input" rows="6" name="book_message" onfocus="if(this.value=='<?php _e('Special Requirements','qns'); ?>')this.value='';" onblur="if(this.value=='')this.value='<?php _e('Special Requirements','qns'); ?>';"><?php 
								if(isset($_POST['book_message'])) : echo $_POST['book_message']; 
								endif;
							?></textarea>
							
							<input type="hidden" name="book_confirm" value="1" />
							<input type="hidden" name="book_room_type" value="<?php echo $book_room_type; ?>" />
							<input type="hidden" name="book_room_price" value="<?php echo $book_room_price; ?>" />
							<input type="hidden" name="submit" value="submit" />
							
							<input class="bookbutton" type="submit" value="<?php _e('Book Now','qns'); ?>" />

						</form>
						
					<!-- END .booknow-accompage -->
					</div>
					
					<?php endif; ?>
				
				<!-- END .one-half -->
				</div>
			
				<!-- BEGIN .one-half -->
				<div class="one-half last-col">
					
					<?php the_content(); ?>
				
				<!-- END .one-half -->
				</div>
				
			<!-- END .even-cols -->
			</div>
			
			<?php endif; ?>
			
		<!-- END .page-content -->
		</div>

	<!-- END .section -->
	</div>
	
<?php get_footer(); ?>
