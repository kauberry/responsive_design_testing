<?php $facets = isset($facets) ? $facets : array(); ?>
<div id="right_col_facets">
  <div id="refineSearch">
  <?php foreach($facets as $facet_category => $facet_items): ?>
    <div class="facet_set" id="facet_set_<?= $facet_category ?>">
      <h3><?= humanize($facet_category) ?></h3>
      <ul class="facet_items" id="facet_items_<?= $facet_category ?>">
        <?php $term_count = 1; ?>
        <?php foreach($facet_items as $facet_name => $facet_count): ?>
          <li class="">
            <?php $checked_state = array_key_exists($facet_category, $checked_facets) && in_array($facet_name,$checked_facets[$facet_category]) ? ' checked="checked"' : ''; ?>
            <input type="checkbox"<?= $checked_state ?> class="facet_checkbox" id="cbx_<?= $facet_category ?>_<?= $term_count?>" name="cbx_<?= $facet_category ?>_<?= $term_count?>">
              <label for="cbx_<?= $facet_category ?>_<?= $term_count?>"><?= $facet_name ?></label> <span class="facet_count">(<?= $facet_count ?>)</span>
            <input type="hidden" class="facet_identifier" id="ident_<?= $facet_category ?>_<?= $term_count?>" name="ident_<?= $facet_category ?>_<?= $term_count?>" value="<?= $facet_name ?>" />
          </li>
          <?php $term_count++; ?>
        <?php endforeach; ?>
      </ul>
      <ul class="refinement_list" style="display:none;">
        <li>
          <span class="expander">+</span>
          <span class="refinement_link see_more">See more&hellip;</span>
        </li>
      </ul>
      <ul id="hidden_facet_items_<?= $facet_category ?>" class="hidden_items see_more_items"></ul>
    </div>
  <?php endforeach; ?>
  </div>
</div>