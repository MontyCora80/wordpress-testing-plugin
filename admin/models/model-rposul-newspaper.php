<?php

class Rposul_Newspaper {

    public static function get_expected_datetime() {
        $tomorrow = new DateTime(); // This object represents current date/time
        $tomorrow->setTime(0, 0, 0); // reset time part, to prevent partial comparison
        $tomorrow->modify("+1 day"); // reset time part, to prevent partial comparison
        return $tomorrow;
    }

    public static function is_configuring_expected_date() {
        $published_date = RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE);
        $expected_datetime = self::get_expected_datetime();
        return $published_date && ($published_date === $expected_datetime->format(RPOSUL_DATE_FORMAT));
    }

    private static function should_cancel($possible_wp_error) {
        if (is_wp_error($possible_wp_error)) {
            print_notice_e('error', true, $possible_wp_error->get_error_message());
            echo '<script>alert("Geração cancelada. ' . $possible_wp_error->get_error_message() . '");</script>';
            return true;
        }
        return false;
    }

    public static function reset() {
        // TODO: melhorar essa função
        Rposul_Page::delete_all();
        RPOSUL_Options::update_option(RPOSUL_OPTION_SELECTED_COVER_TEMPLATE_OPTIONS, array());

        $newspaper_pages = array();

        $cover_page = new Rposul_Page(array('extra_values' => array('is_cover' => true)));
        $newspaper_pages[] = $cover_page;

        $newspaper_pages = self::reset_ads($newspaper_pages);
        $newspaper_pages = self::reset_insertions($newspaper_pages);
        self::save_pages($newspaper_pages);
    }

    private static function save_pages($newspaper_pages) {
        foreach ($newspaper_pages as $page) {
            $page->save();
        }
    }

    public static function reset_insertions($newspaper_pages = null, $auto_save_pages = false) {
        if ($newspaper_pages == null) {
            $newspaper_pages = Rposul_Page::get();
        }

        foreach ($newspaper_pages as $page) {
            if (isset($page->extra_values['insertion_id'])) {
                unset($page->extra_values['insertion_id']);
            }
        }

        $insertions = Rposul_Insertion::get();
        foreach ($insertions as $insertion) {
            while (count($newspaper_pages) < $insertion->page) {
                $new_page = new Rposul_Page();
                $newspaper_pages[] = $new_page;
            }
            $newspaper_pages[$insertion->page - 1]->extra_values['insertion_id'] = $insertion->get_id();
        }

        if ($auto_save_pages) {
            self::save_pages($newspaper_pages);
        }

        return $newspaper_pages;
    }

    /**
     * Check if an advertisement is set for this newspaper and remove it
     * @param Rposul_Advertisement $advertisement
     */
    public static function remove_advertisement($advertisement) {
        $newspaper_pages = Rposul_Page::get();
        foreach ($newspaper_pages as $page) {
            if (is_array($page->advertisement_ids)) {
                if (($key = array_search($advertisement->get_id(), $page->advertisement_ids)) !== false) {
                    unset($page->advertisement_ids[$key]);
                    $page->save();
                }
            }
        }
    }

    /**
     * Checks if all configured advertisements for the configured date are added to the newspaper pages
     * 
     * @param type $newspaper_pages 
     * @return boolean True if all ads are ok or false if there is a missing ad
     */
    public static function check_ads($newspaper_pages = null) {
        $validate_single = !empty($newspaper_pages);
        if ($newspaper_pages == null) {
            $newspaper_pages = Rposul_Page::get();
        }

        $all_ads_ids = array();
        $page_multiple_ads = array();
        foreach ($newspaper_pages as $page) {
            if (count($page->advertisement_ids) > 1) {
                $page_multiple_ads[] = $page->ordinal;
            }
            $all_ads_ids = array_merge($all_ads_ids, $page->advertisement_ids);
        }

        if (!empty($page_multiple_ads)) {
            if (count($page_multiple_ads) == 1) {
                return new WP_Error('multiple', "A página {$page_multiple_ads[0]} possui múltiplos anúncios programados, causando conflito.\n\nPor favor reposicioná-los para que nenhum fique fora da edição.");
            } else {
                return new WP_Error('multiple', "As páginas " . implode(', ', $page_multiple_ads) . " possuem múltiplos anúncios programados, causando conflito.\n\nPor favor reposicioná-los para que nenhum fique fora da edição.");
            }
        }

        $ads = Rposul_Advertisement::get_from_today();

        if (!$validate_single) {
            if (count($ads) != count($all_ads_ids)) {
                return new WP_Error('missing', "Os anúncios desta edição parecem estar desatualizados.");
            }

            $missing_ads_titles = array();
            foreach ($ads as $ad) {
                /* @var $ad Rposul_Advertisement */
                if (!in_array($ad->get_id(), $all_ads_ids)) {
                    $missing_ads_titles[] = $ad->title;
                }
            }

            if (!empty($missing_ads_titles)) {
                return new WP_Error('missing', "Existem anúncios configurados para essa edição que não se encontram presentes em nenhuma das páginas.");
            } else {
                return true;
            }
        }
    }

    public static function reset_ads($newspaper_pages = null, $auto_save_pages = false) {
        if ($newspaper_pages == null) {
            $newspaper_pages = Rposul_Page::get();
        }

        foreach ($newspaper_pages as $page) {
            $page->advertisement_ids = array();
        }

        $ads = Rposul_Advertisement::get_from_today(array('ORDER BY' => "page ASC"));

        foreach ($ads as $ad) {
            while (count($newspaper_pages) < $ad->page) {
                $new_page = new Rposul_Page();
                $newspaper_pages[] = $new_page;
            }
            $newspaper_pages[$ad->page - 1]->advertisement_ids[] = $ad->get_id();
        }

        if ($auto_save_pages) {
            self::save_pages($newspaper_pages);
        }

        return $newspaper_pages;
    }

    public static function generate($quickRun = true, $pages_to_generate = 0) {
        if (!is_array($pages_to_generate)) {
            $pages_to_generate = array($pages_to_generate);
        }
        $filtered_pages = array_filter($pages_to_generate);        
        
        //TODO remove
        //$filtered_pages = array(2,3,4,5, 6);

        $newspaper_pages = Rposul_Page::get();
        for ($index = 0; $index < count($newspaper_pages); $index++) {
            $pagenumber = $index + 1;
            if (!empty($filtered_pages) && !in_array($pagenumber, $filtered_pages)) {
                // We dont need to generate this page
                continue;
            }
            $template_page = $newspaper_pages[$index]->convert_to_template();
            if (is_wp_error($template_page) && $newspaper_pages[$index]->is_cover()) {
                // We allow generating without cover set
                $template_page = new TexCoverBlankTemplate(array());
            }
            if (self::should_cancel($template_page)) {
                return;
            }
            $newspaper_pages[$index] = $template_page;
        }

        /*
         * communicating with server
         */
        if (empty($filtered_pages)) {
            // we clear only if we are generating all pages
            Rposul_Exporter_PyExporterComm::clear_cache();
        }

        $pagenumber = 1;
        for ($index = 0; $index < count($newspaper_pages); $index++) {
            $pagenumber = $index + 1;
            if (empty($filtered_pages) || in_array($pagenumber, $filtered_pages)) {
                $page = $newspaper_pages[$index];
                /* @var $page TexBaseTemplate */
                $retval = $page->export($pagenumber);
                if (self::should_cancel($retval)) {
                    return;
                }
            }
        }

        //TODO change for multiple pages
        $p = reset($filtered_pages);
        $p = !empty($p) ? $p : 0;
        $generate_error = Rposul_Exporter_PyExporterComm::generate(
                        RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE), date('Y-m-d H:i:s'), $quickRun, $p
        );

        if (self::should_cancel($generate_error)) {
            return;
        }


        print_notice_e('success', true, 'Envio para geração realizado com sucesso');
    }

    /**
     * Change de date save in options to a specific date. If the date is changed
     * then the local cache will be erased.
     * 
     * @param str $new_date
     */
    public static function change_date($new_date = null) {
        if (!$new_date) {
            $new_date = self::get_expected_datetime()->format(RPOSUL_DATE_FORMAT);
        }

        $old_date = RPOSUL_Options::get_option(RPOSUL_OPTION_NEWSPAPER_DATE);
        if ($old_date != $new_date) {
            RPOSUL_Options::clear();
            RPOSUL_Options::update_option(RPOSUL_OPTION_NEWSPAPER_DATE, $new_date);
            $rangedate = new DateTime($new_date);
            $rangedate->modify("-1 day");
            RPOSUL_Options::update_option(RPOSUL_OPTION_ARTICLE_START_DATE, $rangedate->format(RPOSUL_DATE_FORMAT));
            RPOSUL_Options::update_option(RPOSUL_OPTION_ARTICLE_END_DATE, $rangedate->format(RPOSUL_DATE_FORMAT));
            self::reset();
            Rposul_Exporter_PyExporterComm::clear_cache();
        }
    }

    public static function close() {
        /*
         * Closing newspaper
         */
        /* $remote_cache = Rposul_Exporter_PyExporterComm::get_cache();
          foreach ($remote_cache['pageposts'] as $pagepost) {
          $pid = $pagepost['post_id'];
          $published_date = $remote_cache['news_date'];
          $post_meta = get_post_meta($pid, RPOSUL_PUBLISHED_DATE_POST_META, false);

          // Do not readd the date if it is already there
          if (!in_array($published_date, $post_meta)) {
          add_post_meta($pid, RPOSUL_PUBLISHED_DATE_POST_META, $published_date);
          }
          }

          Rposul_Page::delete_all();
          RPOSUL_Options::update_option(RPOSUL_OPTION_SELECTED_COVER_TEMPLATE_OPTIONS, array()); */
    }

}
