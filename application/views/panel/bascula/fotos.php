
    <div id="content" class="span12">
      <!-- content starts -->

      <div class="row-fluid">
        <ul class="thumbnails">
    <?php if (count($fotos) > 0) {
      foreach ($fotos as $key => $value) {
    ?>
          <li class="span6 nomarg nopadd">
            <a href="#" class="thumbnail">
              <img src="<?php echo base_url($value->url_foto); ?>" style="width: 100%;height: auto;">
            </a>
          </li>
    <?php
      }
    } ?>
        </ul>
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
