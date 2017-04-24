<?php
/*
  Plugin Name: Like Plugin
  Plugin URI: http://localhost/wordpresss
  Description: Wordpress bir site i�in yaz�lm�� i�erik be�eni eklentisidir.
  Version: Versiyon (0.1 gibi)
  Author: Edibe Nur Karayel
  License: GNU
 */

/* Widget Eylemi Tan�mlan�r */
add_action('widgets_init', 'like_widgets');

/* Widget Eylem Fonksiyonu */

function like_widgets() {
    register_widget('like_widget');
}

/* Widget S�n�f� */

class like_widget extends WP_Widget {
    /* Widget Ba�l��� olu�turulur.
      olu�turulan de�erler WP_Widget metotuna g�nderilir.
     */

    public function __construct() {
        $widget_options = array('description' => 'Bu Like listesinin en �st 10 ��esini g�steren bir widgettir'); //Widget A��klamas�
        parent::WP_Widget(false, 'Like Widget', $widget_options);
    }

    /* gelen yeni form verileri update edilir. */

    public function update($new, $old) {
        return $new;
    }

    /* Admin aray�z�ndeki widget formu olu�turulur. */

    public function form($instance) {
        if (!isset($instance['text']))
            $text = "";
        else
            $text = $instance['text'];

        echo '<p>';
        echo '<label for="' . $this->get_field_id('text') . '">Text</label>';
        echo '<textarea id="' . $this->get_field_id('text') . '" class="widefat" name="' . $this->get_field_name('text') . '">' . $text . '</textarea>';
        echo '</p>';
    }

    /* Widget'�n aray�zde g�r�n�m k�sm� belirlenir. */

    public function widget($args, $instance) {
        global $wpdb;
        $table_posts = $wpdb->prefix . "posts";
        $likes = post_like_cek(); //Her bir post'un id-ba�l�k-be�eni bilgileri dizi olarak d�ner
        $likes = sirala($likes);  //Dizi  be�eni say�lar�na g�re b�y�kten k����e s�ral� olarak d�ner

        $title = apply_filters('widget_title', $instance['title']);
        $blog_title = get_bloginfo('name');
        $tagline = get_bloginfo('description');
        echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title'];
        ?>

        <!-- Top 10 Listesi  -->
        <ul>
        <?php
        for ($i = 0; $i < 10; ++$i) {
            $id = $likes[$i]['ids'];
            $row = $wpdb->get_row("SELECT * FROM $table_posts WHERE ID=$id");
            ?>

                <li >
                    <a href="<?php echo $row->guid; ?>">
            <?php echo $likes[$i]['tags'] . "   [ " . $likes[$i]['begens'] . " begeni]" . $sql->guid; ?>
                    </a>
                </li>
        <?php } ?>
        </ul>
        <?php
        echo $args['after_widget'];
    }

}

/* Direkt �a�r�lar� Engelleme */
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('You are not allowed to call this page directly.');
}


register_activation_hook(__FILE__, 'like_install');

/* Eklenti y�klendi�inde gerekli tablolar� olu�turacak fonksiyon */

function like_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . "likes";
    $table_posts = $wpdb->prefix . "posts";
    if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
        $sql = "CREATE TABLE " . $table_name . " (
				ID bigint(20) NOT NULL AUTO_INCREMENT,
				post_id bigint(20) NOT NULL,
				begen int(11) ,
				PRIMARY KEY (id)
			);";

        $wpdb->query($sql);
    }
}

function like_form() {

    $id = get_the_ID(); //ilgili content'in id sini al�r
    if (isset($_POST['gizli'])) {

        global $wpdb;
        $table_name = $wpdb->prefix . "likes";

        /* wp_likes tablosundan post_id'si ilgili contentin id'sine e�it olan sat�r� �eker */
        $myrows = $wpdb->get_row("SELECT * FROM $table_name WHERE post_id=$id ");

        /* wp_likes tablosunda ilgili post i�in bir sat�r var ise begen kolonunun de�eri 1 artt�r�l�r */
        if ($myrows) {
            $begen = $myrows->begen;
            $begen++;
            $wpdb->update(
                    $table_name, array(
                'begen' => $begen, // string
                    ), array('post_id' => $id,)
            );
        } else {
            /* wp_likes tablosunda ilgili post i�in bir sat�r yok ise(ilk kez be�eniliyor ise) olu�turulur. Begen kolonuna 1 de�eri verilir. */

            $wpdb->insert(
                    $table_name, array(
                'post_id' => $id,
                'begen' => 1
                    )
            );
        }
    }

    /* Like Butonu Formu */
    $form = "<form name='likeform' method='post' action=''>"
            . "<input type='hidden' name='gizli' id='gizli' value='1'/>"
            . "<input type='submit' name='likebtn' id='likebtn' value='Like'>"
            . "</form>";
    return $form;
}

/* Her content i�in �a��r�lacak fonksiyon */

function like_btn($content) {

    if (is_single()) {

        $buton = like_form();
        $content .= $buton; //like_form() fonksiyonundan d�nen "like butonu formu"nu content'in sonuna ekledik.
    }
    return $content;
}

/* Her content i�in like_btn fonksiyonu �a��r�lacak */
add_filter('the_content', 'like_btn');

/* Admin Alan� ��in Be�eni �statistik Sayfas�n� olu�turaca��z. */
add_action('admin_menu', 'begeni_istatistik');

/* Be�eni istatistik sayfas�n�n men�n�n neresine eklenece�ini ve ad�n� belirtiyoruz */

function begeni_istatistik() {
    add_options_page('Like Statistic ', 'Like Statistik', '8', 'like statistic', 'istatistik_fonks');
}

/* Verilen Bir id-tag-like dizisini be�eni say�s�na g�re b�y�kten k����e s�ralayan fonksiyon */

function sirala($dizi) {

    for ($i = 0; $i < $dizi['count'] - 1; $i++) {
        for ($j = $i + 1; $j < $dizi['count']; $j++) {
            if ($dizi[$i]['begens'] < $dizi[$j]['begens']) {
                $tmp['begens'] = $dizi[$i]['begens'];
                $tmp['tags'] = $dizi[$i]['tags'];
                $tmp['ids'] = $dizi[$i]['ids'];

                $dizi[$i]['begens'] = $dizi[$j]['begens'];
                $dizi[$i]['tags'] = $dizi[$j]['tags'];
                $dizi[$i]['ids'] = $dizi[$j]['ids'];

                $dizi[$j]['begens'] = $tmp['begens'];
                $dizi[$j]['tags'] = $tmp['tags'];
                $dizi[$j]['ids'] = $tmp['ids'];
            }
        }
    }
    return $dizi;
}

/* Yay�nda olan t�m post'lar�n id-tag-like  bilgileri dizi olarak d�nd�ren fonksiyon */

function post_like_cek() {
    global $wpdb;
    $tablo = "";
    $table_name = $wpdb->prefix . "likes";
    $table_posts = $wpdb->prefix . "posts";
    $posts = $wpdb->get_results(
            "
	SELECT *
	FROM $table_posts
        WHERE post_status = 'publish' 
	"
    );
    $i = 0;
    foreach ($posts as $post) {
        $dizi[$i]['ids'] = $post->ID;
        $dizi[$i]['tags'] = $post->post_title;
        //$begens[$i]=0;
        $rows = $wpdb->get_row("SELECT * FROM $table_name WHERE post_id= $post->ID ");
        if ($rows) {
            $dizi[$i]['begens'] = $rows->begen;
        } else {
            $dizi[$i]['begens'] = 0;
        }
        $i++;
    }
    $dizi['count'] = $i;

    return $dizi;
}

/* Ayarlar->Like Statistics Se�ene�ine t�kland���nda �al��acak fonksiyondur */

function istatistik_fonks() {
    $likes = post_like_cek(); //Her bir post'un id-ba�l�k-be�eni bilgileri dizi olarak d�ner
    $likes = sirala($likes);  //Dizi  be�eni say�lar�na g�re b�y�kten k����e s�ral� olarak d�ner

    /* Sayfalama i�in gerekli i�lemler */
    $sayfa;         //Bulunulan sayfa say�s�
    $limit = 10;    //Her bir sayfada g�sterilecek tag-like sat�r say�s�
    if (empty($sayfa) || !is_numeric($sayfa)) {
        $sayfa = 1;
    }
    if ($_POST['gizli']) {
        $sayfa = $_POST['sayfa'];   //Formdan gelen sayfa numaras�n� sayfa de�i�kenine atar
    }
    $kayit_sayisi = $likes['count'];
    $sayfa_sayisi = ceil($kayit_sayisi / $limit);
    $baslangic = ($sayfa * $limit) - $limit;
    ?>

    <div style="margin-top:10px;">
        <h2>Like Statistics</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>#Like</th>
            </tr>
    <?php for ($i = $baslangic; $i < $baslangic + $limit; $i++) { ?>

                <tr>
                    <td><?php echo $likes[$i]['ids']; ?></td>
                    <td><?php echo $likes[$i]['tags']; ?></td>
                    <td><?php echo $likes[$i]['begens']; ?></td>
                </tr>

        <?php } ?>
        </table>


        <?php
        /* Sayfa Numaralar� Butonlar�n� G�steren Form */
        echo "<form name='likeform' method='post' action=''>"
        . "<h2><label >Sayfalar</label></h2>";
        for ($sf = 1; $sf <= $sayfa_sayisi; $sf++)
            echo ""
            . "<input type='hidden' name='gizli' id='gizli' value='1'/>"
            . "<input type='submit' name='sayfa' id='sayfa' value='$sf'>"
            . ">"
            ;
        echo "</form>";
        ?>





    </div>

    <?php
}
?>

<style>
    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    td, th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    tr:nth-child(even) {
        background-color: #dddddd;
    }
</style>