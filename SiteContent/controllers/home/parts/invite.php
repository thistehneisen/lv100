<?php
$access_token_data = Settings()->get("invite:access_token");
if (!$access_token_data || $access_token_data["updated"] < time()-3600) {
	$data = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=".Page()->FacebookApp["id"]."&client_secret=".Page()->FacebookApp["secret"]."&grant_type=client_credentials");
	parse_str($data, $token);
	$access_token_data["token"] = $token["access_token"];
	$access_token_data["updated"] = time();
	Settings()->set("invite:access_token",$access_token_data);
}

/*
Dosies = attending_count (bildes no šiem)
Ielūgti = maybe_count + noreply_count
Ieinteresēti = interested_count
*/
$eventData = Settings()->get("invite:event");
if (!$eventData || $eventData["updated"] < time()-60) {
	$event = json_decode(file_get_contents("https://graph.facebook.com/" . Page()->FacebookApp["eventId"] . "/?access_token={$access_token_data["token"]}&fields=attending_count,maybe_count,interested_count,noreply_count,attending{first_name,picture{url}}"));
	$eventData["event"] = $event;
	$eventData["updated"] = time();
	Settings()->set("invite:event",$eventData);
}
	$event = json_decode(json_encode($eventData["event"]));


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
						$people = array_slice($event->attending->data,0,8);
						foreach ($people as $person) {
							echo '						<span class="lg-1-8"><img src="'.$person->picture->data->url.'" alt="'.$person->first_name.'"></span>';
						}
						?>
					</div>
					<div class="row counters">
						<div class="lg-1-3"><span><?php echo number_format($event->attending_count)?></span>dosies</div>
						<div class="lg-1-3"><span><?php echo number_format($event->maybe_count + $event->noreply_count)?></span>ielūgti</div>
						<div class="lg-1-3"><span><?php echo number_format($event->interested_count)?></span>ieinteresēti</div>
					</div>
				</div>

				<p class="going"><b><?php print(join("</b>, <b>",array_map(function($n){ return $n->first_name; },array_slice($people,0,3)))."</b> un <b>".($event->attending_count-3)." citi</b> dosies..."); ?></p>
				<div class="lg-1-1">
					<a href="https://www.facebook.com/events/<?php print(Page()->FacebookApp["eventId"]); ?>/" class="cta cta-go">Došos</a>
					<a href="https://www.facebook.com/events/<?php print(Page()->FacebookApp["eventId"]); ?>/" class="cta cta-invite"><?php include( Page()->bPath.'assets/img/ico/fancy-arrow-right.svg'); ?>Ielūgt citus</a>
				</div>
			</div>

		</div>

	</div>
</section>
