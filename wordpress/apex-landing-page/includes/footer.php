<?php
/**
 * The homepage footer, shared with the case-study templates.
 *
 * Those templates render 100% of their own markup and load only their own
 * stylesheet, so the footer ships as a scoped stylesheet (assets/css/footer.css)
 * rather than by pulling in homepage.css. Section links resolve against the
 * homepage, since the anchors they point at do not exist on a case-study page.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function apex_lp_render_footer() {
	$home = untrailingslashit( apex_lp_homepage_url() );
	$link = function ( $anchor ) use ( $home ) { return esc_url( $home . '/' . $anchor ); };
	?>
<footer class="foot">
  <div class="container foot__grid">
    <div class="foot__brand">
      <span class="mark foot__mark">ΛPEX</span>
      <p class="data data--nano">Paid acquisition for businesses that want to check the numbers.</p>
      <a class="google-partner-badge" href="https://www.google.com/partners/agency?id=6492081901" target="_blank" rel="noopener" aria-label="Apex Marketing is a Google Partner">
        <img src="https://www.gstatic.com/partners/badge/images/2026/PartnerBadgeClickable.svg" width="152" height="145.5" alt="" loading="lazy">
        <span>Google<br>Certified Partner</span>
      </a>
    </div>
    <div><span class="data data--nano foot__k">Services</span><a href="<?php echo $link( '#services' ); ?>">Google Ads</a><a href="<?php echo $link( '#services' ); ?>">Meta Ads</a><a href="<?php echo $link( '#services' ); ?>">SEO</a><a href="<?php echo $link( '#services' ); ?>">Website development</a></div>
    <div><span class="data data--nano foot__k">Company</span><a href="<?php echo $link( '#terms' ); ?>">Terms</a><a href="<?php echo $link( '#pricing' ); ?>">Pricing</a><a href="<?php echo $link( '#founder' ); ?>">About Nathan</a><a href="<?php echo $link( '#book' ); ?>">Book a call</a></div>
    <div><span class="data data--nano foot__k">Contact</span><a href="tel:+18557409608">(855) 740-9608</a><a href="mailto:apex.marketing.ai@gmail.com">apex.marketing.ai@gmail.com</a></div>
  </div>
  <div class="container foot__legal data data--nano">
    <span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Apex Marketing. Results vary by industry, market and competition.</span>
    <span>Privacy &middot; Terms</span>
  </div>
<svg class="foot__morph-filter" aria-hidden="true" focusable="false">
  <defs>
    <!-- The morph blurs each word and this filter re-hardens the edge, which
         is what makes it read as liquid. The dither goes IN that chain, not
         over it: an 8×8 ordered (Bayer) matrix is tiled across the band and
         added to the blurred alpha before the threshold, so the edge breaks
         up into the same ordered dither the relief canvases print with (L14)
         instead of a clean vector contour. Density does the work — where the
         blur is mid-grey the matrix scatters pixels, where it is solid the
         letter stays solid. Additive rather than multiplicative so that a
         browser that drops feImage/feTile degrades to a slightly thinner
         wordmark rather than to nothing. -->
    <filter id="foot-text-threshold" color-interpolation-filters="sRGB">
      <!-- Crush the faint tail first. At the peak of the morph the words sit
           at ~6% opacity under a 100px blur; without this floor the matrix
           would push that haze over the threshold and speckle the whole band
           with dirt instead of leaving it empty. -->
      <feComponentTransfer in="SourceGraphic" result="floored">
        <feFuncA type="linear" slope="1.4" intercept="-0.2" />
      </feComponentTransfer>
      <feImage result="cell" x="0" y="0" width="8" height="8" preserveAspectRatio="none"
        href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAnklEQVR42hXKEXSCYQBA0U4QBEEQDIIgCIJBEAwGQTAIgkEQBIPBIAg6JwiCYBAEQTAIgiAIgkEQBEEQDIJgEAyCQefvfnAfvVgURXF65JiSZECBWUy+eeZEky1VfnkLwyML0nxSZsUD4zD88cGeGv90OPIahhRDisxJ0CfPVxh2vHDhnQ0VzrTC8MSaLBNKLMkwCsONLj80uNLmQP0OXQbfIY1taxIAAAAASUVORK5CYII="
        xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAYAAADED76LAAAAnklEQVR42hXKEXSCYQBA0U4QBEEQDIIgCIJBEAwGQTAIgkEQBIPBIAg6JwiCYBAEQTAIgiAIgkEQBEEQDIJgEAyCQefvfnAfvVgURXF65JiSZECBWUy+eeZEky1VfnkLwyML0nxSZsUD4zD88cGeGv90OPIahhRDisxJ0CfPVxh2vHDhnQ0VzrTC8MSaLBNKLMkwCsONLj80uNLmQP0OXQbfIY1taxIAAAAASUVORK5CYII=" />
      <feTile in="cell" result="bayer" />
      <feComposite in="floored" in2="bayer" operator="arithmetic"
                   k1="0" k2="1" k3="1.3" k4="-0.65" result="scattered" />
      <feColorMatrix in="scattered" type="matrix" values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 255 -140" />
    </filter>
  </defs>
</svg>
  <div class="foot__band" aria-hidden="true"><span class="mark foot__morph" id="footerMorph"><span>ΛPEX</span><span>MARKETING</span></span></div>
</footer>
	<?php
}
