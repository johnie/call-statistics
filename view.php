<?php

class Call_Stats_View {
    private $setup;

    public function __construct($setup) {
        $this->setup= $setup;
    }

    /**
     * Form for adding call record.
     */
    public function getFormHTML() {
        $html = '';
        $html .= '<form id="add-call" action="" method="post"><fieldset>';

        // personal id
        $html .= $this->getTextfield('personal_id', 'Fyll i din personliga identifieringskod:', TRUE);

        // platform
        $html .= $this->getSelect('platform', 'Plattform:', get_option($this->setup->_name . '_platform_options'), TRUE);

        // type
        $html .= $this->getSelect('type', 'Typ av samtal:', get_option($this->setup->_name . '_type_options'), TRUE);

        // minutes
        $html .= $this->getTextfield('minutes', 'Samtalstid i minuter:');

        // gender 
        $html .= $this->getSelect('gender', 'Kön:', get_option($this->setup->_name . '_gender_options'));

        // spouse
        $html .= $this->getSelect('spouse', 'Åldersgrupp:', get_option($this->setup->_name . '_spouse_options'));

        // topic
        $html .= $this->getCheckboxes('topic', 'Samtalsämne:', get_option($this->setup->_name . '_topic_options'));

        // other category
        $html .= $this->getTextarea('other_category', 'Annan samtalskategori:');

        // reference
        $html .= $this->getTextfield('reference', 'Hänvisning:');

        // report
        $html .= $this->getTextarea('report', 'Rapport om vane- eller jourmissbrukare:');

        // response
        $html .= $this->getTextarea('response', 'Hur bemötte du vane-eller jourmissbrukaren?');

        $html .= '<input name="' . $this->setup->_name . '_post" type="hidden" value="1">';
        $html .= '<input type="submit" value="Submit" class="btn btn-primary">';
        $html .= '</fieldset></form>';

        return $html;
    }

    public function getListHTML() {
        $cur_year = date('Y');
        $cur_month = date('m');

        $query_year = isset($_GET['year']) ? $_GET['year'] : $cur_year;
        $query_month = isset($_GET['month']) ? $_GET['month'] : $cur_month;

        $results = $this->getCalls($query_year, $query_month);
        $header = array('PID', 'Platform', 'Typ', 'Minuter', 'Kön', 'Åldersgrupp', 'Samtalsämne', 'Annan', 'Hänvisning', 'Rapport', 'Bemötte', 'Datetime');
        $rows = array();
        foreach ($results as $call) {
            $rows[] = array(
                $call->personal_id,
                $call->platform,
                $call->type,
                $call->minutes,
                $call->gender,
                $call->spouse,
                $call->topics,
                $call->other_category,
                $call->reference,
                $call->report,
                $call->response,
                $call->created,
            );
        }

        $html = '';

        $html .= '<form action="" method="get" class="form-inline">';
        $html .= '<select name="year" class="input-small">';
        foreach (range($cur_year - 10, $cur_year) as $year) {
            $html .= '<option value="' . $year . '" ' . selected($year, isset($_GET['year']) ? $_GET['year'] : $cur_year, FALSE) . '>' . $year . '</option>';
        }
        $html .= '</select>';
        $html .= '<select name="month" class="input-small">';
        foreach (range(1, 12) as $month) {
            $html .= '<option value="' . $month. '" ' . selected($month, isset($_GET['month']) ? $_GET['month'] : $cur_month, FALSE) . '>' . $month . '</option>';
        }
        $html .= '</select>';
        $html .= '<input type="hidden" name="page_id" value="' . $_GET['page_id'] . '">';
        $html .= '<input type="hidden" name="list" value="' . $_GET['list'] . '">';
        $html .= '<input type="submit" class="btn btn-primary" value="change">';
        $html .= '</form>';

        $html .= $this->getTableHTML($header, $rows);

        return $html;
    }

    private function getCalls($year = NULL, $month = NULL) {
        if (!$year) {
            $year = intval(date('Y'));
            $month = intval(date('m'));
        }
        else if (!$month) {
            $month = date('m');
        }

        $sql = "
            SELECT *
            FROM {$this->setup->calls_table_name} c
            JOIN (
                SELECT
                    call_id,
                    GROUP_CONCAT(topic) as topics
                FROM {$this->setup->call_topic_table_name}
                GROUP BY call_id
            ) ct
            ON c.id = ct.call_id
            WHERE YEAR(c.created) = $year
            AND MONTH(c.created) = $month
        ";

        global $wpdb;
        return $wpdb->get_results($sql);
    }

    /**
     * Generate statistics page.
     */
    public function getStatsHTML() {
        $html = "";
        global $wpdb;

        $sql = "SELECT topic, count(1) AS total FROM {$this->setup->call_topic_table_name} GROUP BY topic";
        $result = $wpdb->get_results($sql);
        $header = array("Samtalsämne", "#");
        $rows = array();
        foreach ($result as $item) {
            $rows[] = array($item->topic, $item->total);
        }
        $html .= $this->getTableHTML($header, $rows);

        $stats_types = array(
            "personal_id" => "Personliga Identifieringskod",
            "platform" => "Plattform",
            "type" => "Typ av samtal",
            "gender" => "Kön",
            "spouse" => "Åldersgrupp",
            "DATE_FORMAT(created, '%Y-%m')" => "Year-month",
        );

        foreach ($stats_types as $field => $display) {
            $sql = "SELECT $field, count(1) AS total FROM {$this->setup->calls_table_name} GROUP BY $field";
            $result = $wpdb->get_results($sql);
            $header = array($display, "#");
            $rows = array();
            foreach ($result as $item) {
                $rows[] = array($item->{$field}, $item->total);
            }
            $html .= $this->getTableHTML($header, $rows);
        }

        return $html;
    }

    private function getTextfield($name, $label, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<input id="' . $id . '" type="text" name="' . $name . '" value="' . (isset($_POST[$name]) ? $_POST[$name] : '') . '">';
        return '<div>' . $html . '</div>';
    }

    private function getTextarea($name, $label, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<div><textarea id="' . $id . '" name="' . $name .'">' . (isset($_POST[$name]) ? $_POST[$name] : '') . '</textarea></div>';
        return '<div>' . $html . '</div>';
    }

    private function getSelect($name, $label, $options, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<select id="' . $id . '" name="' . $name .'">';
        foreach ($options as $option) {
            $html .= '<option value="' . $option . '" ' . selected(isset($_POST[$name]) ? $_POST[$name] : '', $option, TRUE) . '>' . $option . '</option>';
        }
        $html .= '</select>';
        return '<div>' . $html . '</div>';
    }

    private function getCheckboxes($name, $label, $options, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<span>' . $label . '</span>' . ($required ? '<span class="required">*</span>' : '');
        foreach ($options as $index => $option) {
            $html .= '<label for="' . $id . '-' . $index . '">';
            $html .= '<input id="' . $id . '-' . $index . '" type="checkbox" name="' . $name . '[]" value="' . $option . '" ' . ((isset($_POST[$name]) && in_array($option, $_POST[$name])) ? 'checked="checked"' : '') . '> ' . $option;
            $html .= '</label>';
        }
        return '<div>' . $html . '</div>';
    }

    /**
     * Helper for generate table.
     */
    private function getTableHTML($header, $rows, $title = "") {
        $html = '<table class="stats table table-striped table-hover">';

        $html .= '<tr>';
        $col = 0;
        foreach ($header as $item) {
            $html .= '<th class="col-' . $col++ . '">' . $item . '</th>';
        }
        $html .= '</tr>';

        $odd_even = array("odd", "even");
        $count = 0;
        foreach ($rows as $row) {
            $html .= '<tr class="' . $odd_even[$count++ % 2] . '">';
            $col = 0;
            foreach ($row as $item) {
                $html .= '<td class="col-' . $col++ . '">' . $item . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        if ($title) {
            $html = '<h2>' . $title . '</h2>' . $html;
        }

        return $html;
    }
}
