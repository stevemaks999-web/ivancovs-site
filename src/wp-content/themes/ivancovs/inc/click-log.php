<?php
/**
 * Учёт кликов по телефону и WhatsApp с источником.
 * Хранятся только счётчики по дням (без IP, без cookie, без личных данных).
 */
defined( 'ABSPATH' ) || exit;

define( 'IVS_CLICKS_OPT', 'ivs_click_counts' );

add_action( 'rest_api_init', function () {
	register_rest_route( 'ivs/v1', '/click', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$p = $req->get_json_params();
			$t = isset( $p['t'] ) && 'wa' === $p['t'] ? 'wa' : ( isset( $p['t'] ) && 'call' === $p['t'] ? 'call' : '' );
			if ( ! $t ) {
				return new WP_REST_Response( array( 'ok' => false ), 400 );
			}
			$s = strtolower( preg_replace( '/[^a-z0-9._\-]/i', '', substr( (string) ( $p['s'] ?? '' ), 0, 40 ) ) );
			$s = $s ? $s : 'direct';
			ivs_click_add( $t, $s );
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		},
	) );
} );

function ivs_click_add( $type, $src ) {
	$all = get_option( IVS_CLICKS_OPT, array() );
	$all = is_array( $all ) ? $all : array();
	$d   = wp_date( 'Y-m-d' );
	$key = $type . '|' . $src;
	if ( ! isset( $all[ $d ] ) ) {
		$all[ $d ] = array();
	}
	if ( ! isset( $all[ $d ][ $key ] ) && count( $all[ $d ] ) >= 60 ) {
		$key = $type . '|other';
	}
	$all[ $d ][ $key ] = ( $all[ $d ][ $key ] ?? 0 ) + 1;
	$cut               = wp_date( 'Y-m-d', strtotime( '-400 days' ) );
	foreach ( array_keys( $all ) as $k ) {
		if ( $k < $cut ) {
			unset( $all[ $k ] );
		}
	}
	update_option( IVS_CLICKS_OPT, $all, false );
}

/** Сумма кликов за период: array( 'call' => n, 'wa' => n, 'src' => array( 'google' => n ) ) */
function ivs_click_stats( $from, $to ) {
	$all = get_option( IVS_CLICKS_OPT, array() );
	$r   = array( 'call' => 0, 'wa' => 0, 'src' => array() );
	foreach ( (array) $all as $d => $rows ) {
		if ( $d < $from || $d > $to ) {
			continue;
		}
		foreach ( $rows as $key => $n ) {
			list( $t, $s ) = explode( '|', $key, 2 );
			$r[ $t ]      += $n;
			$r['src'][ $s ] = ( $r['src'][ $s ] ?? 0 ) + $n;
		}
	}
	arsort( $r['src'] );
	return $r;
}

add_action( 'wp_footer', function () {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	$url = esc_url_raw( rest_url( 'ivs/v1/click' ) );
	?>
<script>
(function(){
  var u=<?php echo wp_json_encode( $url ); ?>;
  function src(){
    var s='';
    try{
      var q=new URLSearchParams(location.search).get('utm_source');
      if(q){s=q;sessionStorage.setItem('ivs_src',s);}
      else{
        s=sessionStorage.getItem('ivs_src')||'';
        if(!s&&document.referrer){var h=new URL(document.referrer).hostname.replace(/^www\./,'');if(h&&h!==location.hostname.replace(/^www\./,'')){s=h;sessionStorage.setItem('ivs_src',s);}}
      }
    }catch(e){}
    return s||'direct';
  }
  document.addEventListener('click',function(e){
    var a=e.target.closest&&e.target.closest('a[href]');
    if(!a)return;
    var h=a.getAttribute('href')||'';
    var t=h.indexOf('tel:')===0?'call':(h.indexOf('wa.me')>-1||h.indexOf('whatsapp.com')>-1)?'wa':'';
    if(!t)return;
    var b=JSON.stringify({t:t,s:src()});
    try{if(navigator.sendBeacon){navigator.sendBeacon(u,new Blob([b],{type:'application/json'}));return;}}catch(x){}
    try{fetch(u,{method:'POST',headers:{'Content-Type':'application/json'},body:b,keepalive:true});}catch(x){}
  },true);
})();
</script>
	<?php
}, 98 );
