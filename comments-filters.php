<?php
/*
Plugin Name: Comments Filters
Plugin URI: #
Description: With Comments Filters you can filter your comments by users type, comments date and type of comments: if have a reply or not.
Version: 1.0
Author: Marco Peca
Author URI: https://www.marcopeca.it/
*/

class Comments_Filters {

    public $debug;

    public function __construct($debug = FALSE) {
        $this->debug = $debug;
        add_action( 'admin_init', array($this, 'setup_hooks'));
    }

    public function setup_hooks(){
        add_action( 'restrict_manage_comments', array($this,'wpce3_author_comments_include_filter'));
        //add_action( 'restrict_manage_comments', array($this,'wpce3_author_comments_exclude_filter'));
        add_action( 'restrict_manage_comments', array($this,'wpce3_date_comments_filter'));
        add_action( 'restrict_manage_comments', array($this,'wpce3_type_comments_filter'));

        add_filter( 'comments_clauses', array( $this, 'wpce3_comment_sort_query' ),10,2 );
    }

    public function wpce3_comment_sort_query($clauses){
        $filter_include_author = $this->get_current_author_include_filter();
        $filter_exclude_author = $this->get_current_author_exclude_filter();
        $filter_type = $this->get_current_type_filter();
        $filter_date = $this->get_current_date_filter();
        
        if($this->debug){
            echo '<div id="wpbody" role="main">
            <div id="wpbody-content">';
            $this->debug_on_screen("Includi ","h3");
            var_dump($filter_include_author);

            $this->debug_on_screen("Escludi ","h3");
            var_dump($filter_exclude_author);

            $this->debug_on_screen("Filtro Tipo Commento ","h3");
            var_dump($filter_type);

            $this->debug_on_screen("Data Commenti","h3");
            var_dump($filter_date);
            echo '</div></div>';
        }

        global $wpdb;
        $pref = $wpdb->prefix;        

        $clauses["where"] = "(( ".$pref."comments.comment_approved = '0' OR ".$pref."comments.comment_approved = '1' ))";
        
        //$str = explode("AND",$clauses['where']);
        //$clauses['where'] = $str[0];        


        // Includi autori
        if($filter_include_author == "0"){
            $clauses['where'] .= " AND ".$pref."comments.user_id = 0";            
        } else if($filter_include_author != "" && $filter_include_author != "0"){
            $clauses['join'] = " , ".$pref."usermeta";            
            $clauses['where'] .= " AND ".$pref."usermeta.meta_key = '".$pref."capabilities'";
            $clauses['where'] .= " AND ".$pref."usermeta.meta_value LIKE '%".$filter_include_author."%'";
            $clauses['where'] .= " AND ".$pref."usermeta.user_id = ".$pref."comments.user_id";
        }

        // Escludi autori
        if($filter_exclude_author == "0"){
            $clauses['where'] .= " AND ".$pref."comments.user_id = 0";
        } else if($filter_exclude_author != "" && $filter_exclude_author != "0"){
            $clauses['join'] = " , ".$pref."usermeta";
            $clauses['where'] .= " AND ".$pref."usermeta.meta_key = '".$pref."capabilities'";
            $clauses['where'] .= " AND ".$pref."usermeta.meta_value NOT LIKE '%".$filter_exclude_author."%'";
            $clauses['where'] .= " AND ".$pref."usermeta.user_id = ".$pref."comments.user_id";
        }
        
        //  Commenti con o senza risposta        
        $subquery = "SELECT C.comment_parent FROM ".$pref."comments C WHERE C.comment_parent != 0";
        if($filter_type == "0"){            
            $clauses['where'] .= " AND ".$pref."comments.comment_ID NOT IN ($subquery)";
        } else if($filter_type == "1"){
            $clauses['where'] .= " AND ".$pref."comments.comment_ID IN ($subquery)";
        }        

        //  Filtro per data
        if($filter_date !=  ""  && $filter_date != "0"){
            $clauses['where'] .= " AND ".$pref."comments.comment_date  BETWEEN DATE_SUB(NOW(), INTERVAL $filter_date DAY) AND NOW()";
        }
        
        if($this->debug){
            var_dump($clauses);            
        }
        return $clauses;        
    }

    public function wpce3_type_comments_filter(){
        $filter_type = $this->get_current_type_filter();

        echo '<label class="screen-reader-text" for="type_comment_filter">' . esc_html__( 'Filtra per Tipo di Commento', 'comments-filters' ) . '</label>';
        echo '<select name="type_comment_filter" id="">';
        if($filter_type == ""){
            echo $this->generate_option( '', __( 'Tutti i commenti', 'comments-filters' ),TRUE);
        } else {
            echo $this->generate_option( '', __( 'Tutti i commenti', 'comments-filters' ));
        }

        if($filter_type == "1"){
            echo $this->generate_option( '1', __( 'Commenti con risposta', 'comments-filters' ),TRUE);
        } else {
            echo $this->generate_option( '1', __( 'Commenti con risposta', 'comments-filters' ));
        }

        if($filter_type == "0"){
            echo $this->generate_option( '0', __( 'Commenti senza risposta', 'comments-filters' ),TRUE);
        } else {
            echo $this->generate_option( '0', __( 'Commenti senza risposta', 'comments-filters' ));        
        }

        echo '</select>';
    }
    
    public function wpce3_date_comments_filter(){
        $filter_date = $this->get_current_date_filter();        

        echo '<label class="screen-reader-text" for="date_comment_filter">' . esc_html__( 'Filtra per Data Commento', 'comments-filters' ) . '</label>';
        echo '<select name="date_comment_filter" id="">';
        if($filter_date == ""){
            echo $this->generate_option( '', __( 'Tutte le date', 'comments-filters' ),TRUE);
        } else {
            echo $this->generate_option( '', __( 'Tutte le date', 'comments-filters' ));
        }

        if($filter_date == "1"){
            echo $this->generate_option( '1', __( 'Ultime 24 Ore', 'comments-filters' ),TRUE);
        } else {
            echo $this->generate_option( '1', __( 'Ultime 24 Ore', 'comments-filters' ));
        }

        if($filter_date == "3"){
            echo $this->generate_option( '3', __( 'Ultimi 3 giorni', 'comments-filters' ), TRUE);
        } else {
            echo $this->generate_option( '3', __( 'Ultimi 3 giorni', 'comments-filters' ));
        }

        if($filter_date == "7"){
            echo $this->generate_option( '7', __( 'Ultimi 7 giorni', 'comments-filters' ), TRUE);
        } else {
            echo $this->generate_option( '7', __( 'Ultimi 7 giorni', 'comments-filters' ));
        }

        if($filter_date == "30"){
            echo $this->generate_option( '30', __( 'Ultimi 30 giorni', 'comments-filters' ), TRUE);
        } else {
            echo $this->generate_option( '30', __( 'Ultimi 30 giorni', 'comments-filters' ));
        }

        echo '</select>';
    }

    public function wpce3_author_comments_include_filter(){
        $filter = $this->get_current_author_include_filter();

        echo '<label class="screen-reader-text" for="author_comment_inlude_filter">' . esc_html__( 'Filtra per Autore Commento', 'comments-filters' ) . '</label>';
        echo '<select name="author_comment_inlude_filter" id="">';
        echo $this->generate_option( '', __( 'Utenti da includere', 'comments-filters' ));
        if($filter != "" && $filter == "0"){
            echo $this->generate_option( '0', __( 'Utenti non loggati', 'comments-filters' ), TRUE);       
        }  else  {
            echo $this->generate_option( '0', __( 'Utenti non loggati', 'comments-filters' ) );
        } 

        if($filter != "" && $filter != "0"){
            echo wp_dropdown_roles($filter);
        }  else {
            echo wp_dropdown_roles();    
        }        

        echo '</select>';
    } 

    public function wpce3_author_comments_exclude_filter(){
        $filter = $this->get_current_author_exclude_filter();

        echo '<label class="screen-reader-text" for="author_comment_exclude_filter">' . esc_html__( 'Filtra per Autore Commento', 'comments-filters' ) . '</label>';
        echo '<select name="author_comment_exclude_filter" id="">';
        echo $this->generate_option( '', __( 'Utenti da escludere', 'comments-filters' ));
        if($filter != "" && $filter == "0"){
            echo $this->generate_option( '0', __( 'Utenti non loggati', 'comments-filters' ), TRUE);       
        }  else  {
            echo $this->generate_option( '0', __( 'Utenti non loggati', 'comments-filters' ) );
        } 

        if($filter != "" && $filter != "0"){
            echo wp_dropdown_roles($filter);
        }  else {
            echo wp_dropdown_roles();    
        }        

        echo '</select>';
    } 

    public function get_current_date_filter(){
        return filter_input( INPUT_GET, 'date_comment_filter' );
    }

    public function get_current_type_filter(){
        return filter_input( INPUT_GET, 'type_comment_filter' );
    }

    public function get_current_author_include_filter() {
        return filter_input( INPUT_GET, 'author_comment_inlude_filter' );
    }

    public function get_current_author_exclude_filter() {
        return filter_input( INPUT_GET, 'author_comment_exclude_filter' );
    }

    protected function generate_option( $value, $label, $selected = FALSE ) {
        $selected_text = "";
        if($selected)
            $selected_text  = "selected='selected'";
        return '<option ' . $selected_text . ' value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
    }

    protected function debug_on_screen($paragraph,$tag){
        echo "<$tag>$paragraph</$tag>";
    }

}

new Comments_Filters();