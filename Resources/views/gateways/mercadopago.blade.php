@if($gateway->endpoint === 'mercadopago-argentina')
    <div class="form-group">
        <label>Access Token</label>
        <input type="password" name="config[access_token]" 
               value="{{ $gateway->config['access_token'] ?? '' }}" 
               class="form-control" required>
    </div>
    
    <div class="form-group">
        <label>Public Key</label>
        <input type="text" name="config[public_key]" 
               value="{{ $gateway->config['public_key'] ?? '' }}" 
               class="form-control" required>
    </div>

    <div class="form-group">
        <label>
            <input type="checkbox" name="config[test_mode]" 
                   @checked($gateway->config['test_mode'] ?? false)> Modo Sandbox
        </label>
    </div>
@endif