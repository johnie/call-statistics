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

        if (is_super_admin()) {
            $permalink = get_permalink();
            $glue = strstr($permalink, '?') === FALSE ? '?' : '&';
            $html .= '<div class="super-admin-op">';
            $html .= '<a href="' . $permalink . $glue . 'stats=1" class="btn btn-info">Statistik</a>';
            $html .= ' <a href="' . $permalink . $glue . 'list=1" class="btn btn-info">Lista</a>';
            $html .= '</div>';
        }

        $html .= '<form id="add-call" action="" method="post"><fieldset>';

        // personal id
        $html .= $this->getTextfield('personal_id', 'Fyll i din personliga identifieringskod:', TRUE);

        // type
        $options = get_option($this->setup->_name . '_type_options');
        $html .= $this->getSelect('type', 'Typ av samtal:', array_combine($options, $options), TRUE);

        // minutes
        $html .= $this->getTextfield('minutes', 'Samtalslängd (minuter):', TRUE);

        // gender 
        $options = get_option($this->setup->_name . '_gender_options');
        $html .= $this->getSelect('gender', 'Kön:', array_combine($options, $options));

        // age
        $options = get_option($this->setup->_name . '_age_options');
        $html .= $this->getSelect('age', 'Åldersgrupp:', array_combine($options, $options));

        // topic
        $html .= $this->getCheckboxes('topic', 'Samtalsämne:', get_option($this->setup->_name . '_topic_options'));

        // other category
        $html .= $this->getTextarea('other_category', 'Annan samtalskategori:');

        $html .= '<input name="' . $this->setup->_name . '_post" type="hidden" value="1">';
        $html .= '<input type="submit" value="Hämta" class="btn btn-primary">';
        $html .= '</fieldset></form>';

        return $html;
    }

    public function getListHTML() {
        $cur_year = intval(date('Y'));
        $cur_month = intval(date('m'));

        $query_year = isset($_GET['year']) ? $_GET['year'] : $cur_year;
        $query_month = isset($_GET['month']) ? $_GET['month'] : $cur_month;

        $results = $this->getCalls($query_year, $query_month);
        $header = array('PID', 'Typ', 'Minuter', 'Kön', 'Åldersgrupp', 'Samtalsämne', 'Annan', 'Datetime');
        $rows = array();
        foreach ($results as $call) {
            $rows[] = array(
                $call->personal_id,
                $call->type,
                $call->minutes,
                $call->gender,
                $call->age,
                $call->topics,
                $call->other_category,
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
        $html .= '<input type="submit" class="btn btn-primary" value="Byt">';
        $html .= '<a href="' . get_page_link($this->setup->page_id) . '" class="btn btn-cancel">Tillbaka</a>';
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
            LEFT JOIN (
                SELECT
                    call_id,
                    GROUP_CONCAT(topic) as topics
                FROM {$this->setup->call_topic_table_name}
                GROUP BY call_id
            ) ct
            ON c.id = ct.call_id
            WHERE YEAR(c.created) = $year
            AND MONTH(c.created) = $month
            ORDER BY created DESC
        ";

        global $wpdb;
        return $wpdb->get_results($sql);
    }

    /**
     * Generate statistics page.
     */
    public function getStatsHTML() {
        global $wpdb;

        $options = array(
            'type'     => get_option($this->setup->_name . '_type_options'),
            'gender'   => get_option($this->setup->_name . '_gender_options'),
            'age'      => get_option($this->setup->_name . '_age_options'),
        );

        $group_by = isset($_POST['group_by']) && in_array($_POST['group_by'], array('type', 'gender', 'age', 'topic')) ? $_POST['group_by'] : FALSE;

        $selects = array('COUNT(1) AS total', 'SUM(minutes) AS minutes_total');
        if ($group_by) $selects[] = $group_by;

        $wheres = array();
        $fields = array('type', 'gender', 'age');
        foreach ($fields as $field) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                // if all checked, no need to set as condition
                if (count($_POST[$field]) >= count($options[$field])) continue;

                $values = array();
                foreach ($_POST[$field] as $value) {
                    $values[] = '"' . sanitize_text_field($value) . '"';
                }

                if (count($_POST[$field]) > 1) {
                    $wheres[] = $field . ' IN (' . implode(', ', $values) . ')';
                }
                else {
                    $wheres[] = $field . ' = ' . $values[0];
                }
            }
        }

        // minutes
        if (isset($_POST['min_minutes']) && !empty($_POST['min_minutes'])) {
            $wheres[] = 'minutes > ' . intval($_POST['min_minutes']);
        }

        if (isset($_POST['max_minutes']) && !empty($_POST['max_minutes'])) {
            $wheres[] = 'minutes < ' . intval($_POST['max_minutes']);
        }

        $sql = 'SELECT ' . implode(', ', $selects) . ' FROM ' . $this->setup->calls_table_name . ' calls';

        if ('topic' == $group_by) {
            $sql .= ' LEFT JOIN ' . $this->setup->call_topic_table_name . ' call_topic ON calls.id = call_topic.call_id';
        }

        if (!empty($wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }
        if ($group_by) {
            $sql .= " GROUP BY $group_by";
        }

        $result = $wpdb->get_results($sql);

        $html = '';

        if ($group_by) {
            $display = array(
                "type"     => "Typ av samtal",
                "gender"   => "Kön",
                "age"      => "Åldersgrupp",
                "topic"    => "Samtalsämne",
            );
            $header = array($display[$group_by], "#", "#minuter");
            $rows = array();
            foreach ($result as $item) {
                $rows[] = array($item->$group_by ? $item->$group_by : '- Inget -', $item->total, $item->minutes_total);
            }

            $html .= $this->getTableHTML($header, $rows);
        }
        else {
            $html .= '<div class="alert alert-success">Hittade ' .  $result[0]->total . ' samtal (' . $result[0]->minutes_total . ' minuter).</div>';
        }

        $html .= $this->getStatsPanel();
        return $html;
    }

    /**
     * return stats form.
     */
    private function getStatsPanel() {
        $html = '';
        $html .= '<form method="post" action="" class="stats-panel">';

        $html .= '<fieldset>';
        $html .= '<div class="alert alert-warning">Att kryssa i samtliga val i en kategori har samma funktion som att inte kryssa i någon.</div>';
        $html .= '<div class="checkboxes">' . $this->getCheckboxes('type', 'Typ av samtal:', get_option($this->setup->_name . '_type_options'), FALSE, FALSE) . '</div>';
        $html .= '<div class="checkboxes">' . $this->getCheckboxes('gender', 'Kön:', get_option($this->setup->_name . '_gender_options'), FALSE, FALSE) . '</div>';
        $html .= '<div class="checkboxes">' . $this->getCheckboxes('age', 'Åldersgrupp:', get_option($this->setup->_name . '_age_options'), FALSE, FALSE) . '</div>';
        $html .= '<div>' . $this->getTextfield('min_minutes', 'Samtalstid längre än (ange i hela minuter)') . '</div>';
        $html .= '<div>' . $this->getTextfield('max_minutes', 'Samtalstid kortare än (ange i hela minuter)') . '</div>';
        $html .= '</fieldset>';

        $html .= '<fieldset>';
        $html .= $this->getSelect('group_by', 'Gruppering:', array(
            '_none_'   => '- None -',
            'type'     => 'Typ av samtal',
            'gender'   => 'Kön',
            'age'      => 'Åldersgrupp',
            'topic'    => 'Samtalsämne',
        ));
        $html .= '</fieldset>';

        $html .= '<input class="btn btn-primary" type="submit" value="Skicka">';
        $html .= '<a href="' . get_page_link($this->setup->page_id) . '" class="btn btn-cancel">Tillbaka</a>';

        $html .= '</form>';

        return $html;
    }

    private function getTextfield($name, $label, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<input id="' . $id . '" type="text" name="' . $name . '" value="' . (isset($_REQUEST[$name]) ? $_REQUEST[$name] : '') . '">';
        return $html;
    }

    private function getTextarea($name, $label, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<div><textarea id="' . $id . '" name="' . $name .'">' . (isset($_REQUEST[$name]) ? $_REQUEST[$name] : '') . '</textarea></div>';
        return $html;
    }

    private function getSelect($name, $label, $options, $required = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label for="' . $id . '">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        $html .= '<select id="' . $id . '" name="' . $name .'">';
        foreach ($options as $value => $display) {
            $html .= '<option value="' . $value. '" ' . selected(isset($_REQUEST[$name]) ? $_REQUEST[$name] : '', $value, FALSE) . '>' . $display. '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    private function getCheckboxes($name, $label, $options, $required = FALSE, $inline = FALSE) {
        $html = '';
        $id = "call-" . str_replace('_', '-', $name);
        $html .= '<label class="checkboxes-label">' . $label . ($required ? '<span class="required">*</span>' : '') . '</label>';
        foreach ($options as $index => $option) {
            $html .= '<label for="' . $id . '-' . $index . '" class="checkbox' . ($inline ? ' inline' : '') . '">';
            $html .= '<input id="' . $id . '-' . $index . '" type="checkbox" name="' . $name . '[]" value="' . $option . '" ' . ((isset($_REQUEST[$name]) && in_array($option, $_REQUEST[$name])) ? 'checked="checked"' : '') . '> ' . $option;
            $html .= '</label>';
        }
        return $html;
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
