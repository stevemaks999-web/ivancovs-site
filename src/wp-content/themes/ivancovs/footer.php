<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer><div class="wrap">
	<div><b style="color:#fff">IVANCOVS</b><br>SIA “Delumix” · Rīga<br>Lastādijas iela 32, LV-1050</div>
	<div><a href="mailto:<?php echo esc_attr( IVS_EMAIL ); ?>"><?php echo esc_html( IVS_EMAIL ); ?></a><br><a href="tel:+37125553244"><?php echo esc_html( IVS_PHONE ); ?></a></div>
	<div><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php ivs_e( 'Privātuma politika', 'Политика конфиденциальности', 'Privacy policy' ); ?></a></div>
</div></footer>
<?php wp_footer(); ?>
</body>
</html>
