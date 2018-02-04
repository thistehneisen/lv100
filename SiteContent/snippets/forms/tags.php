<div class="tags">
	<h5>Tags input field</h5>
	<form action="" class="test" method="post">
		<label for="exist-values">
			<input type="text" id="exist-values" class="tagged form-control" data-removeBtn="true" name="tag-2" value="PC,Play Station 4,X-BOX One" placeholder="Add Platform">
		</label>
		<div>
			<button id="destroy">destroy</button>
			<button id="add">add tags</button>
			<button id="addArr">add tags array</button>
			<button id="clear">clear tags</button>
			<button id="get">get taggs</button>
		</div>
		<button type="submit" name="button">Send</button>
	</form>

	<script src="<?php print($this->bHost); ?>assets/js/tags.js"></script>
</div>