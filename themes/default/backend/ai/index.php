<?php $__view->layout('layouts.backend'); ?>

<?php $__view->start('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>AI Assistant</h1>
    </div>
    
    <?php if (!$ai): ?>
    <div class="alert alert-warning">
        <h5><i class="bi bi-exclamation-triangle"></i> AI Not Configured</h5>
        <p class="mb-0">
            Configure your AI provider API key in the <code>.env</code> file to enable AI features.
        </p>
    </div>
    <?php else: ?>
    
    <!-- AI Chat -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">AI Chat</h5>
                </div>
                <div class="card-body">
                    <div id="chatMessages" class="mb-3" style="max-height: 400px; overflow-y: auto;">
                        <div class="alert alert-info">
                            <strong>AI Assistant</strong><br>
                            Hello! I can help you create content, optimize SEO, generate page structures, and more. 
                            What would you like to do?
                        </div>
                    </div>
                    
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="text" 
                               id="chatInput" 
                               class="form-control" 
                               placeholder="Ask the AI assistant..."
                               autocomplete="off">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="generatePage()">
                            <i class="bi bi-file-earmark-plus"></i> Generate Page Structure
                        </button>
                        <button class="btn btn-outline-success" onclick="generatePost()">
                            <i class="bi bi-journal-plus"></i> Generate Blog Post
                        </button>
                        <button class="btn btn-outline-info" onclick="optimizeSEO()">
                            <i class="bi bi-graph-up"></i> Optimize SEO
                        </button>
                        <button class="btn btn-outline-warning" onclick="generateIdeas()">
                            <i class="bi bi-lightbulb"></i> Content Ideas
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Usage Stats -->
            <?php if (!empty($usageStats)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Usage (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $totalRequests = array_sum(array_column($usageStats, 'requests'));
                    $totalTokens = array_sum(array_column($usageStats, 'tokens'));
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Requests:</span>
                        <strong><?= number_format($totalRequests) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Tokens:</span>
                        <strong><?= number_format($totalTokens) ?></strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<script>
document.getElementById('chatForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    
    const messagesDiv = document.getElementById('chatMessages');
    messagesDiv.innerHTML += `
        <div class="alert alert-secondary">
            <strong>You:</strong><br>${escapeHtml(message)}
        </div>
    `;
    
    input.value = '';
    input.disabled = true;
    
    try {
        const response = await fetch('/api/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': '<?= $__view->config("api_key", "") ?>'
            },
            body: JSON.stringify({ message })
        });
        
        const data = await response.json();
        
        messagesDiv.innerHTML += `
            <div class="alert alert-info">
                <strong>AI:</strong><br>${escapeHtml(data.response || data.error || 'No response')}
            </div>
        `;
    } catch (err) {
        messagesDiv.innerHTML += `
            <div class="alert alert-danger">
                <strong>Error:</strong><br>${escapeHtml(err.message)}
            </div>
        `;
    }
    
    input.disabled = false;
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function generatePage() {
    const desc = prompt('Describe the page you want to create:');
    if (desc) {
        fetch('/api/ai/generate-structure', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': '<?= $__view->config("api_key", "") ?>'
            },
            body: JSON.stringify({ type: 'page', description: desc })
        }).then(r => r.json()).then(data => {
            if (data.structure) {
                alert('Structure generated! Check the console for details.');
                console.log(JSON.stringify(data.structure, null, 2));
            } else {
                alert(data.error || 'Generation failed');
            }
        });
    }
}

function generatePost() {
    const topic = prompt('Enter the blog post topic:');
    if (topic) {
        fetch('/api/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': '<?= $__view->config("api_key", "") ?>'
            },
            body: JSON.stringify({ 
                message: 'Write a blog post about: ' + topic 
            })
        }).then(r => r.json()).then(data => {
            alert('Content generated! Check the console for details.');
            console.log(data.response);
        });
    }
}

function optimizeSEO() {
    const content = prompt('Enter content to optimize:');
    if (content) {
        fetch('/admin/ai/optimize-seo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content })
        }).then(r => r.json()).then(data => {
            alert('SEO optimized! Check console for results.');
            console.log(data);
        });
    }
}

function generateIdeas() {
    const topic = prompt('Enter a topic for content ideas:');
    if (topic) {
        fetch('/api/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': '<?= $__view->config("api_key", "") ?>'
            },
            body: JSON.stringify({ 
                message: 'Generate 5 content ideas for: ' + topic + '. Format as a list with titles and brief descriptions.' 
            })
        }).then(r => r.json()).then(data => {
            alert('Ideas generated! Check console for details.');
            console.log(data.response);
        });
    }
}
</script>
<?php $__view->stop(); ?>
