<?php
require __DIR__ . '/../app/controllers/auth.php';
require_login();
require_once 'includes/waf.php'; // WAF protection
ob_start();
?>

<div class="row justify-content-center">
  <div class="col-md-10">
    <div class="card p-4">
      <h2 class="mb-3">Welcome to the AI Guard Management Portal</h2>
      <p>This portal allows you to manage <strong>Nemo Guardrail</strong> and <strong>Persona</strong> YAML configuration files for your AI applications.</p>

      <div class="text-center my-4">
        <img src="images/AI_Guard_Manager.png" alt="AI Guard Manager Flow Diagram" class="img-fluid rounded shadow-sm" style="max-height: 600px;" />
      </div>

       <h4 class="mt-5">Core Concepts</h4>
      <div class="row mt-3">
        <div class="col-md-6">
          <div class="card border-success mb-3">
            <div class="card-body">
              <h5 class="card-title">Agent</h5>
              <p class="card-text">A complete AI assistant or service that combines a model, a persona, and one or more guardrails to achieve a goal (e.g., TravelAdvisorBot).</p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card border-info mb-3">
            <div class="card-body">
              <h5 class="card-title">Persona</h5>
              <p class="card-text">Describes who the AI is - including tone, personality, and role (e.g., friendly financial advisor, robotic tech support).</p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card border-warning mb-3">
            <div class="card-body">
              <h5 class="card-title">Guardrail</h5>
              <p class="card-text">A policy enforcement layer that blocks, logs, or restricts AI behavior to ensure security, ethics, and compliance.</p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card border-dark mb-3">
            <div class="card-body">
              <h5 class="card-title">Model</h5>
              <p class="card-text">The large language model (LLM) itself - such as GPT-4 - which powers the AI's responses, configured with temperature, max tokens, etc.</p>
            </div>
          </div>
        </div>
      </div>

      <hr>
      <p class="text-muted">Use the navigation bar above to access all sections of the portal. Don't forget to <a href="logout.php">log out</a> when done.</p>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php
$content = ob_get_clean();
include 'includes/layout.php';
