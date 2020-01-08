<button type="button" class="filter-bar__button has-sub js-dropdown-toggle" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</button>
<div class="dropdown">
	<div class="dropdown__search">
		<div class="search-input">
		<input
			type="text"
			name="<?=$name?>"
			value="<?=htmlentities($custom_value, ENT_QUOTES, 'UTF-8')?>"
			placeholder="<?=htmlentities($placeholder, ENT_QUOTES, 'UTF-8')?>"
			rel="date-picker"
			class="search-input__input"
			<?php if ($timestamp): ?>data-timestamp="<?=$timestamp?>" <?php endif; ?>
		>
		</div>
	</div>
	<?php foreach ($options as $url => $label): ?>
		<a class="dropdown__link" href="<?=$url?>"><?=$label?></a>
	<?php endforeach; ?>
</div>
