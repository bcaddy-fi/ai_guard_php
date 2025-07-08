<?php
require __DIR__ . '/../app/controllers/auth.php';
require __DIR__ . '/../app/controllers/db.php';
require_login();

$directory = __DIR__ . '/../data/persona/';
$files = glob($directory . '*.yaml');
$selectedFile = $_GET['file'] ?? null;
$personaYaml = '';
if ($selectedFile && file_exists($directory . $selectedFile)) {
    $personaYaml = file_get_contents($directory . $selectedFile);
}
ob_start();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<div class="container mt-5">
  <div class="card p-4 mb-4">
    <h3>Available Personas</h3>
    <ul class="list-group">
      <?php foreach ($files as $file): 
        $basename = basename($file); ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><?= htmlspecialchars($basename) ?></span>
          <div>
            <a href="manage_personas.php?file=<?= urlencode($basename) ?>" class="btn btn-sm btn-secondary">Edit</a>
            <a href="test_persona.php?file=<?= urlencode($basename) ?>" class="btn btn-sm btn-success">Test</a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <?php if ($selectedFile && $personaYaml): ?>
  <div class="card p-4">
    <h4>Testing Persona: <?= htmlspecialchars($selectedFile) ?></h4>
    <form id="aiTestForm">
      <input type="hidden" name="persona" value="<?= htmlspecialchars($personaYaml) ?>">
      <input type="hidden" name="filename" value="<?= htmlspecialchars($selectedFile) ?>">
      <div class="mb-3">
        <label for="prompt" class="form-label">Prompt</label>
        <textarea name="prompt" class="form-control" rows="3" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Send</button>
    </form>

    <div id="responseContainer" class="mt-4 d-none">
      <h5>AI Response:</h5>
      <div class="alert alert-success" id="responseText" style="white-space: pre-wrap;"></div>
    </div>

    <div id="errorContainer" class="mt-4 d-none">
      <h5>Error:</h5>
      <div class="alert alert-danger" id="errorText"></div>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('aiTestForm');
  if (!form) return;

  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    const formData = new FormData(form);
    const responseContainer = document.getElementById('responseContainer');
    const errorContainer = document.getElementById('errorContainer');
    const responseText = document.getElementById('responseText');
    const errorText = document.getElementById('errorText');

    responseContainer.classList.add('d-none');
    errorContainer.classList.add('d-none');

    try {
      const response = await fetch('test_api.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.choices && data.choices[0].message && data.choices[0].message.content) {
        const result = data.choices[0].message.content;
        responseText.textContent = result;
        responseContainer.classList.remove('d-none');

        await fetch('/api/log_api_interaction.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            prompt: formData.get('prompt'),
            response: result,
            filename: formData.get('filename'),
            user: '<?= $_SESSION['user_id'] ?? 'unknown' ?>',
            policy_type: 'persona'
          })
        });

      } else {
        errorText.textContent = JSON.stringify(data.error || 'Unexpected response format.');
        errorContainer.classList.remove('d-none');
      }
    } catch (err) {
      console.error('Request error:', err);
      errorText.textContent = 'Failed to reach API or parse response.';
      errorContainer.classList.remove('d-none');
    }
  });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/includes/layout.php';
include __DIR__ . '/includes/footer.php';
