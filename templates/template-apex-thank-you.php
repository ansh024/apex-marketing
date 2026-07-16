<?php
/**
 * Apex Marketing - Thank You page template.
 * Selected via Page Attributes → Template → "Apex - Thank You".
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$apex_img = APEX_LP_URL . 'assets/images/';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( get_the_title() ? get_the_title() . ' | Apex Marketing' : "Thanks - We've Got Your Request | Apex Marketing" ); ?></title>
<meta name="description" content="Thanks for booking your free strategy call with Apex Marketing. Nathan will reach out within one business day.">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2255FA">
<link rel="stylesheet" href="<?php echo esc_url( APEX_LP_URL . 'assets/css/main.css' ); ?>">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Inter:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&family=Titillium+Web:wght@300;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  .thanks {
    position: relative;
    overflow: hidden;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 100px 24px;
    color: #fff;
    background: linear-gradient(139deg, #2255FA 0%, #0C37B7 100%);
  }
  .thanks__gradient { position: absolute; inset: 0; z-index: 0; width: 100%; height: 100%; }
  .thanks__inner { position: relative; z-index: 2; max-width: 640px; }
  .thanks__icon {
    width: 72px; height: 72px; margin: 0 auto 28px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.28);
    box-shadow: inset 0 0 22px rgba(255, 255, 255, 0.08);
  }
  .thanks__icon svg { width: 32px; height: 32px; }
  .thanks h1 { font-family: var(--serif); font-weight: 700; font-size: clamp(2rem, 4.4vw, 3rem); line-height: 1.25; margin-bottom: 16px; }
  .thanks p { font-size: 1.1rem; color: rgba(255, 255, 255, 0.82); margin-bottom: 36px; }
  .thanks__next { text-align: left; display: inline-flex; flex-direction: column; gap: 12px; margin: 0 auto 40px; }
  .thanks__next li { display: flex; gap: 10px; align-items: baseline; color: #d8ffdd; font-size: 0.95rem; font-weight: 600; }
  .thanks__next li::before { content: "✓"; color: var(--lime); font-weight: 700; }
  .thanks__logo { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 48px; }
  .thanks__logo img { width: 140px; height: auto; }
</style>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<section class="thanks">
  <canvas id="thanksGradient" class="thanks__gradient" aria-hidden="true"></canvas>
  <div class="thanks__inner">
    <a class="thanks__logo" href="<?php echo esc_url( apex_lp_landing_url() ); ?>" aria-label="Apex Marketing">
      <img src="<?php echo esc_url( $apex_img . 'apex-logo.png' ); ?>" alt="Apex Marketing" width="219" height="75">
    </a>
    <div class="thanks__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 12.5L9.5 18L20 6" stroke="#00F81D" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h1>You're booked in - thank you.</h1>
    <p>We've got your request. Nathan will personally reach out within one business day to schedule your free strategy call and come prepared with real ideas for your market.</p>
    <ul class="thanks__next">
      <li>Check your inbox for a confirmation email</li>
      <li>Nathan reviews your practice &amp; market before the call</li>
      <li>No pitch, no pressure - just a real plan</li>
    </ul>
    <div>
      <a class="btn btn--signal btn--lg" href="<?php echo esc_url( apex_lp_landing_url() ); ?>">Back to Homepage</a>
    </div>
  </div>
</section>

<script type="module">
  import { NeatGradient } from "https://esm.sh/@firecms/neat";
  const canvas = document.getElementById("thanksGradient");
  if (canvas) {
    new NeatGradient({
      ref: canvas,
      colors: [
        { color: "#2255FA", enabled: true },
        { color: "#3D6BFF", enabled: true },
        { color: "#0C37B7", enabled: true },
        { color: "#071C42", enabled: true },
        { color: "#02102A", enabled: true },
        { color: "#B8D4E6", enabled: false },
      ],
      speed: 2,
      horizontalPressure: 3,
      verticalPressure: 5,
      waveFrequencyX: 1,
      waveFrequencyY: 3,
      waveAmplitude: 8,
      shadows: 0,
      highlights: 2,
      colorBrightness: 1,
      colorSaturation: 6,
      wireframe: false,
      colorBlending: 7,
      backgroundColor: "#0C37B7",
      backgroundAlpha: 1,
      grainScale: 2,
      grainSparsity: 0,
      grainIntensity: 0.175,
      grainSpeed: 1,
      resolution: 1,
      yOffset: 0,
      yOffsetWaveMultiplier: 1.8,
      yOffsetColorMultiplier: 2,
      yOffsetFlowMultiplier: 2.2,
      flowEnabled: false,
      shapeType: "plane",
      cameraLock: true,
    });
  }
</script>

<?php wp_footer(); ?>
</body>
</html>
