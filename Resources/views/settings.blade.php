<form class="form-horizontal margin-top margin-bottom" method="POST" action="" id="saml_form">
    {{ csrf_field() }}

    <div class="form-group{{ $errors->has('settings.vkintegration.access_token') ? ' has-error' : '' }} margin-bottom-10">
        <label for="vkintegration.access_token" class="col-sm-2 control-label">{{ __('Access Token') }}</label>

        <div class="col-sm-6">
            <input id="vkintegration.access_token" type="text" class="form-control input-sized-lg" name="settings[vkintegration.access_token]" value="{{ old('settings.vkintegration.access_token', $settings['vkintegration.access_token']) }}">
            @include('partials/field_error', ['field'=>'settings.vkintegration.access_token'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.vkintegration.confirmation_code') ? ' has-error' : '' }} margin-bottom-10">
        <label for="vkintegration.confirmation_code" class="col-sm-2 control-label">{{ __('Confirmation Code') }}</label>

        <div class="col-sm-6">
            <input id="vkintegration.confirmation_code" type="text" class="form-control input-sized-lg" name="settings[vkintegration.confirmation_code]" value="{{ old('settings.vkintegration.confirmation_code', $settings['vkintegration.confirmation_code']) }}">
            @include('partials/field_error', ['field'=>'settings.vkintegration.confirmation_code'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.vkintegration.default_mailbox') ? ' has-error' : '' }} margin-bottom-10">
        <label for="vkintegration.default_mailbox" class="col-sm-2 control-label">{{ __('Default Mailbox') }}</label>

        <div class="col-sm-6">
            <input id="vkintegration.default_mailbox" type="text" class="form-control input-sized-lg" name="settings[vkintegration.default_mailbox]" value="{{ old('settings.vkintegration.default_mailbox', $settings['vkintegration.default_mailbox']) }}">
            @include('partials/field_error', ['field'=>'settings.vkintegration.default_mailbox'])
        </div>
    </div>

    <div class="form-group{{ $errors->has('settings.vkintegration.secret') ? ' has-error' : '' }} margin-bottom-10">
        <label for="vkintegration.secret" class="col-sm-2 control-label">{{ __('Secret') }}</label>

        <div class="col-sm-6">
            <input id="vkintegration.secret" type="text" class="form-control input-sized-lg" name="settings[vkintegration.secret]" value="{{ old('settings.vkintegration.secret', $settings['vkintegration.secret']) }}">
            @include('partials/field_error', ['field'=>'settings.vkintegration.secret'])
        </div>
    </div>

    <div class="form-group margin-top margin-bottom">
        <div class="col-sm-6 col-sm-offset-2">
            <button type="submit" class="btn btn-primary">
                {{ __('Save') }}
            </button>
        </div>
    </div>
</form>