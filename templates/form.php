<div id="new_event_form">
			
	<form id="new_post" name="new_post" method="post" action="<?php the_permalink(); ?>">
	
		<h2><?php _e('Event Information'); ?></h2>
		
		<div class="input">
			<label for="title"><?php _e('Event Name','emefs'); ?> <small><?php _e('required','emefs'); ?></small></label><br />
			<?php EMEFS::field('event_name'); ?>
			<?php EMEFS::error('event_name'); ?>
		</div>
		
		<div class="input select">
			<label for="event_category_ids"><?php _e('Select the Event Category','emefs'); ?> <small><?php _e('required','emefs'); ?></small></label><br/>
			<?php EMEFS::field('event_category_ids'); ?>
			<?php EMEFS::error('event_category_ids'); ?>
		</div>
		
		<fieldset>
		
			<legend><?php _e('Date & Time','emefs'); ?></legend>
		
			<fieldset id="event_start">
				<legend><?php _e('Start','emefs'); ?> <small><?php _e('required','emefs'); ?></small></legend>
				<div class="input">
					<label for="event_start_date"><?php _e('Date','emefs'); ?></label>
					<?php EMEFS::field('event_start_date'); ?>
					<?php EMEFS::error('event_start_date'); ?>
				</div>
				<div class="input">
					<label for="event_start_time"><?php _e('Time','emefs'); ?></label> 
					<?php EMEFS::field('event_start_time'); ?>
					<?php EMEFS::error('event_start_time'); ?>
				</div>
			</fieldset>
			
			<fieldset id="event_end">
				<legend><?php _e('End','emefs'); ?></legend>
				<div class="input">
					<label for="event_end_date"><?php _e('Date','emefs'); ?></label>
					<?php EMEFS::field('event_end_date'); ?>
					<?php EMEFS::error('event_end_date'); ?>
				</div>
				<div class="input">
					<label for="event_end_time"><?php _e('Time','emefs'); ?></label> 
					<?php EMEFS::field('event_end_time'); ?>
					<?php EMEFS::error('event_end_time'); ?>
				</div>
			</fieldset>
			
			<?php EMEFS::error('event_time'); ?>
		
		</fieldset>
		
		<div class="input">
			<label for="event_description"><?php _e('Description','emefs'); ?> <small><?php _e('required','emefs'); ?></small></label><br />
			<?php EMEFS::field('event_notes'); ?>
			<?php EMEFS::error('event_notes'); ?>
		</div>
		
		<div class="input">
			<label for="event_contactperson_email_body"><?php _e('Contact E-mail','emefs'); ?></label><br />
			<?php EMEFS::field('event_contactperson_email_body'); ?>
			<?php EMEFS::error('event_contactperson_email_body'); ?>
		</div>
		
		<div class="input">
			<label for="event_url"><?php _e('Event Web Page','emefs'); ?></label><br />
			<?php EMEFS::field('event_url'); ?>
			<?php EMEFS::error('event_url'); ?>
		</div>
		
		<h3><?php _e('Location Information','emefs'); ?></h3>
		
		<div class="input">
			<label for="location_name"><?php _e('Name','emefs'); ?></label>
			<?php EMEFS::field('location_name'); ?>
			<?php EMEFS::error('location_name'); ?>
		</div>
		
		<div class="input">
			<label for="location_address"><?php _e('Address','emefs'); ?></label>
			<?php EMEFS::field('location_address'); ?>
			<?php EMEFS::error('location_address'); ?>
		</div>
		
		<div class="input">
			<label for="location_town"><?php _e('City or Town','emefs'); ?></label>
			<?php EMEFS::field('location_town'); ?>
			<?php EMEFS::error('location_town'); ?>
		</div>
		
		<div class="map">
			<div id="event-map"></div>
			<?php EMEFS::field('location_latitude'); ?>
			<?php EMEFS::field('location_longitude'); ?>
		</div>
		
		<p class="submit">
			<?php EMEFS::end_form('Submit Event','emefs'); ?>
		</p>
		
	</form>

</div>
