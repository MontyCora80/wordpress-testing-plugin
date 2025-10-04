<div class="wrap">

    <div id="icon-options-general" class="icon32"></div>


    <h2>        
        <?php
        if (!$is_new) {
            echo "Editar Anúncio";
            echo '<a href="?page=' . Rposul_Exporter_Admin_Page_Edit_Ads::MENU_SLUG . '" class="add-new-h2">' . __("Add") . '</a>';
        } else {
            echo "Adicionar Anúncio";
        }
        ?>
    </h2>

    <div id="poststuff">
        <form method="post" novalidate name="rposul_exporter_add_section_form">

            <div id="post-body" class="metabox-holder columns-2">

                <!-- main content -->
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">

                        <div class="postbox">

                            <h2 class="hndle"><span>Informações</span></h2>

                            <div class="inside">
                                <div class="eo-grid-row">
                                    <div class="eo-grid-4">
                                        <span class="eo-label" id="eo-start-datetime-label">
                                            Título
                                        </span>
                                    </div>
                                    <div class="eo-grid-8">
                                        <input type="text" name="ad_title" size="30" value="<?php echo $title; ?>" spellcheck="true" autocomplete="off">
                                    </div>
                                </div>


                                <div class="eo-grid onetime">

                                    <div class="eo-grid-row">
                                        <div class="eo-grid-4">
                                            <span class="eo-label" id="eo-start-datetime-label">
                                                Início de publicação
                                            </span>
                                        </div>
                                        <div class="eo-grid-8 event-date" role="group" aria-labelledby="eo-start-datetime-label">

                                            <label for="eo-start-date" class="screen-reader-text"><?php esc_html_e('Início de publicação'); ?></label>
                                            <input type="text" id="eo-start-date" aria-describedby="eo-start-date-desc" class="ui-widget-content ui-corner-all" name="eo_input[StartDate]" size="10" maxlength="10" readonly value="<?php echo $start->format($php_format); ?>"/>
                                        </div>
                                    </div>
                                    <div class="eo-grid-row event-date">
                                        <div class="eo-grid-4">
                                            <label for="eo-event-recurrence"><?php esc_html_e('Recorrência:'); ?> </label>
                                        </div>
                                        <div class="eo-grid-8 event-date"> 
                                            <?php
                                            $recurrence_schedules = array(
                                                'once' => __('apenas uma vez'), 'daily' => __('diariamente'), 'weekly' => __('semanalmente'),
                                                'monthly' => __('mensalmente'), 'yearly' => __('anualmente'), 'custom' => __('customizado'),
                                            );
                                            ?>
                                            <select id="eo-event-recurrence" name="eo_input[schedule]">
                                                <?php foreach ($recurrence_schedules as $value => $label) : ?>
                                                    <option value="<?php echo esc_attr($value) ?>" <?php selected($schedule, $value); ?>><?php echo esc_html($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="eo-grid-row event-date reocurrence_row">
                                        <div class="eo-grid-4"></div>
                                        <div class="eo-grid-8 event-date">
                                            <div id="eo-recurrence-frequency-wrapper">
                                                Repetir a cada
                                                <label for="eo-recurrence-frequency" class="screen-reader-text"><?php esc_html_e('Recorrência'); ?></label> 
                                                <input type="number" id="eo-recurrence-frequency" class="ui-widget-content ui-corner-all" name="eo_input[event_frequency]"  min="1" max="365" maxlength="4" size="4" value="<?php echo intval($frequency); ?>" /> 
                                                <span id="eo-recurrence-schedule-label"></span>
                                            </div>

                                            <div id="eo-day-of-week-repeat">

                                                <span id="eo-days-of-week-label" class="screen-reader-text"><?php esc_html_e('Repete nos dias da semana:'); ?></span>
                                                <ul class="eo-days-of-week" role="group" aria-labelledby="eo-days-of-week-label">	
                                                    <?php
                                                    for ($i = 0; $i <= 6; $i++) :
                                                        $d = ($start_day + $i) % 7;
                                                        $ical_d = $ical_days[$d];
                                                        $day = $wp_locale->weekday_abbrev[$wp_locale->weekday[$d]];
                                                        $fullday = $wp_locale->weekday[$d];
                                                        $schedule_days = ( is_array($schedule_meta) ? $schedule_meta : array() );
                                                        ?>
                                                        <li>
                                                            <input type="checkbox" id="day-<?php echo esc_attr($day); ?>"  <?php checked(in_array($ical_d, $schedule_days), true); ?>  value="<?php echo esc_attr($ical_d) ?>" class="daysofweek" name="eo_input[days][]"/>
                                                            <label for="day-<?php echo esc_attr($day); ?>" > <abbr aria-label="<?php echo esc_attr($fullday); ?>"><?php echo esc_attr($day); ?></abbr></label>
                                                        </li>
                                                        <?php
                                                    endfor;
                                                    ?>
                                                </ul>
                                            </div>

                                            <div id="eo-day-of-month-repeat">
                                                <span id="eo-days-of-month-label" class="screen-reader-text"><?php esc_html_e('Selecione se deseja repetir mensalmente por data ou dia:'); ?></span>
                                                <div class="eo-days-of-month" role="group" aria-labelledby="eo-days-of-month-label">	
                                                    <label for="eo-by-month-day" >
                                                        <input type="radio" id="eo-by-month-day" name="eo_input[schedule_meta]" <?php checked($occurs_by, 'BYMONTHDAY'); ?> value="BYMONTHDAY=" /> 
                                                        <?php esc_html_e('dia do mês'); ?>
                                                    </label>
                                                    <label for="eo-by-day" >
                                                        <input type="radio" id="eo-by-day" name="eo_input[schedule_meta]"  <?php checked('BYMONTHDAY' != $occurs_by, true); ?> value="BYDAY=" />
                                                        <?php esc_html_e('dia da semana'); ?>
                                                    </label>
                                                </div>
                                            </div>

                                            <div id="eo-schedule-last-date-wrapper" class="reoccurrence_label">
                                                <?php esc_html_e('até'); ?>
                                                <label id="eo-repeat-until-label" for="eo-schedule-last-date" class="screen-reader-text"><?php esc_html_e('Repetir este anúnco até:'); ?></label> 
                                                <input class="ui-widget-content ui-corner-all" name="eo_input[schedule_end]" id="eo-schedule-last-date" size="10" maxlength="10" readonly value="<?php echo $until->format($php_format); ?>"/>
                                            </div>

                                            <p id="eo-event-summary" role="status" aria-live="polite"></p>

                                        </div>
                                    </div>

                                    <div id="eo_occurrence_picker_row" class="eo-grid-row event-date">
                                        <div class="eo-grid-4">
                                            <?php esc_html_e('Incluir/Excluir ocorrências:'); ?>
                                        </div>
                                        <div class="eo-grid-8 event-date">
                                            <?php submit_button(__('Mostrar datas'), 'hide-if-no-js eo_occurrence_toggle button small', 'eo_date_toggle', false); ?>

                                            <div id="eo-occurrence-datepicker">
                                                <input type="hidden" name="eo_input[include]" id="eo-occurrence-includes" value="<?php echo $include; ?>"/>
                                                <input type="hidden" name="eo_input[exclude]" id="eo-occurrence-excludes" value="<?php echo $exclude; ?>"/>

                                            </div>
                                        </div>
                                    </div>
                                    <div id="eo_image_picker_row" class="eo-grid-row">
                                        <div class="eo-grid-4">
                                            Anúncio
                                        </div>
                                        <div class="eo-grid-8 event-date">
                                            <div class="thumbnail">
                                                <div class="selected_image_container">
                                                    <?php
                                                    if (!empty($image_id)) {
                                                        $attachment_attr = rposul_get_attachment($image_id);
                                                        if (!$attachment_attr) {
                                                            $attachment_url = RPOSUL_PLACEHOLDER_DEFAULT_IMAGE;
                                                        } else {
                                                            $attachment_url = $attachment_attr['url'];
                                                        }
                                                    } else {
                                                        $attachment_url = RPOSUL_PLACEHOLDER_DEFAULT_IMAGE;
                                                    }
                                                    ?>
                                                    <img class="upload_image_thumbnail" data-picker-id="imagepicker" width="240" height="240" src="<?php echo $attachment_url; ?>" class="img-responsive">
                                                </div>
                                            </div>
                                            <?php submit_button('Selecionar imagem', 'hide-if-no-js button small upload_image_button', 'upload_image_button', false, array('data-picker-id' => 'imagepicker')); ?>
                                            <input name='upload_image_input' type="hidden" data-picker-id="imagepicker" value="<?php echo $image_id; ?>" />
                                        </div>
                                    </div>
                                </div>
                                <!-- .inside -->

                            </div>
                            <!-- .postbox -->

                        </div>
                        <!-- .meta-box-sortables .ui-sortable -->

                    </div>

                    <?php if ($date_modified): ?>
                        <p class="object_footnote">Última modificação em <?php echo rposul_get_mysqldatetime_from_datetime($date_modified); ?>.</p>
                    <?php endif; ?>

                    <?php if ($date_created): ?>                    
                        <p class="object_footnote">Criado em <?php echo rposul_get_mysqldatetime_from_datetime($date_created); ?>.</p>
                    <?php endif; ?>
                </div>

                <!-- sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="meta-box-sortables">
                        <div class="postbox">
                            <h2 class="hndle"><span>Configurações</span></h2>
                            <div class="inside">
                                <label for="eo-event-type">Posição</label>
                                <select id="rposul-ad-type" name="eo_input[type]">
                                    <?php
                                    foreach (Rposul_Advertisement::$TYPE_OPTIONS as $value => $label) {
                                        $selected = selected($type, $value, false);
                                        echo "<option value='$value' $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div id="rposul-ad-section-pages-div" class="inside"> 
                                <label for="rposul-ad-section-pages">Página</label> 
                                <input type="number" id="rposul-ad-section-pages" class="ui-widget-content ui-corner-all" name="eo_input[page]"  min="1" max="365" maxlength="4" size="4" value="<?php echo intval($ad_page); ?>" /> 
                            </div>
                            <!-- .inside -->
                        </div>                        

                        <div class="postbox">
                            <h2 class="hndle"><span>Salvar Anúncio</span></h2>
                            <div class="inside">
                                <?php
                                submit_button('Salvar', 'button-primary', 'publish-ad', true);
                                ?>
                            </div>                            
                            <!-- .inside -->
                        </div>
                    </div>
                    <!-- .meta-box-sortables -->
                </div>
                <input type="hidden" name='rposul_exporter_ads_submitted' value="Y"/>
            </div>
            <!-- #poststuff -->
        </form>
    </div> <!-- .wrap -->

    <!-- This file should primarily consist of HTML with a little bit of PHP. -->

