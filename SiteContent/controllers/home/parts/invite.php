<?php
$access_token_data = Settings()->get("invite:access_token");
if (!$access_token_data || $access_token_data["updated"] < time()-3600) {
	$data = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=912881472177929&client_secret=1df53e74749385f22a361e518927b39d&grant_type=client_credentials");
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
$event = json_decode(file_get_contents("https://graph.facebook.com/808275699312698/?access_token={$access_token_data["token"]}&fields=attending_count,maybe_count,interested_count,noreply_count,attending{first_name,picture{url}}"));
?>
<section>
	<div class="container invite-block">
		<header>
			<h2>Latvija tevi ielūdz svinēt</h2>
			<p class="lead">Nulla pretium vulputate elit ac faucibus. Curabitur quis lacinia ligula. Aliquam felis nulla, tincidunt sed eleifend id, feugiat a augue. Maecenas hendrerit convallis blandit. Suspendisse ullamcorper, neque vel tristique ullamcorper.</p>
		</header>

		<a class="video play youtube-player lg-3-5 sm-1-2 xs-1-1" href="https://youtu.be/LL998ajnjN4">
			<span class="img-ct">
				<img src="<?php print(Page()->bHost); ?>assets/img/placeholder-rect.svg">
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

				<p class="going"><b>Paula,</b> <b>Diāna</b> un <b>47 citi draugi</b> dosies</p>
				<div class="lg-1-1">
					<a href="#" class="cta cta-go">Došos</a>
					<a href="#" class="cta cta-invite"><?php include( Page()->bPath.'assets/img/ico/fancy-arrow-right.svg'); ?>Ielūgt citus</a>
				</div>
			</div>

		</div>

	</div>
</section>
