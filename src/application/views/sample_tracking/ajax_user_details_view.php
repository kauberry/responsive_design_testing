<div id="user_details_<?= $contact_id ?>" class="user_details_insert">
  <table>
    <tr>
      <td class="user_details_label">Name:</td>
      <td class="user_details_item"><span id="user_<?= $contact_id ?>_full_name" class="user_full_name"><?= $full_name ?></span></td>
    </tr>
    <tr>
      <td class="user_details_label">Address:</td>
      <td class="user_details_item">
        <span id="user_<?= $contact_id ?>_address" class="user_address">
          <?= $street_address ?><br />
          <?= $city ?>, <?= $state_province ?> <?= $postal_code ?><br />
          <?= $country ?>
        </span>
      </td>
    </tr>
  </table>
</div>
