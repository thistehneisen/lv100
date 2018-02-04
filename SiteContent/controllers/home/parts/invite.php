<?php

	$eventData = Settings()->get("event:data:" . Page()->FacebookApp["eventId"]);


	$fpage = Settings("first_page_data");

?>
<section>
	<div class="container invite-block">
		<header>
			<h2><?php print($fpage["cb"]["title"]); ?></h2>
			<p class="lead"><?php print($fpage["cb"]["description"]); ?></p>
		</header>

		<a class="<?php print(preg_match("#youtu(\.be|be\.com)#", $fpage["cb"]["address"]) ? 'youtube-player play' : ''); ?> video lg-3-5 sm-1-2 xs-1-1" href="<?php print($fpage["cb"]["address"]); ?>">
			<span class="img-ct">
				<img src="<?php print(Page()->host); ?><?php print($fpage["cb"]["picture"]); ?>">
			</span>
		</a>
		<div class="lg-2-5 sm-1-2 xs-1-1 desc">
			<div class="lg-1-1 xs-1-1 status">
				<h3>Latvijas simtgades svinības</h3>

				<div class="lg-1-1 xs-1-1 guests">
					<div class="row profile-thumbs">
						<?php
						$people = $eventData["attending"];
						foreach ($people as $person) {
							echo '						<span class="lg-1-8"><img src="'.$person[1].'" alt="'.$person[0].'"></span>';
						}
						?>
					</div>
					<div class="row counters">
						<div class="lg-1-3"><span><?php echo number_format($eventData["attending_count"])?></span>dosies</div>
						<div class="lg-1-3"><span><?php echo number_format($eventData["maybe_count"] + $eventData["noreply_count"])?></span>ielūgti</div>
						<div class="lg-1-3"><span><?php echo number_format($eventData["interested_count"])?></span>ieinteresēti</div>
					</div>
				</div>

				<p class="going"><b><?php print(join("</b>, <b>",array_map(function($n){ return $n[0]; },array_slice($eventData["attending"],0,3)))."</b> un <b>".($eventData["attending_count"]-3)." citi</b> dosies..."); ?></p>
				<div class="lg-1-1">
					<a href="https://www.facebook.com/events/<?php print(Page()->FacebookApp["eventId"]); ?>/" class="cta cta-go">Došos</a>
					<a href="https://www.facebook.com/events/<?php print(Page()->FacebookApp["eventId"]); ?>/" class="cta cta-invite"><?php include( Page()->bPath.'assets/img/ico/fancy-arrow-right.svg'); ?>Ielūgt citus</a>
				</div>
			</div>

		</div>

	</div>
</section>
