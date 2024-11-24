@include('admin.partials.nav')
<div class="container">
    <div class="row">
        <div class="col-md">
            <?php
            if (session()->has('auth_is_admin') && session()->get('auth_is_admin') == 1) { ?>
            <button class="btn btn-danger" onclick="fetch('<?php echo url('/admin/cache') ?>', {method: 'DELETE'})">
                Clear Database Cache
            </button>
            <?php } ?>
            <table id="settingsTable" class="table table-striped table-borderless table-responsive">
                <thead>
                <tr>
                    <th scope="col">Setting</th>
                    <th scope="col">Value</th>
                    <th scope="col">Current Source</th>
                    <th scope="col">Default</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($settings->allowlist() as $key => $value)
                    @if (!$value['hidden'])
                        <tr>
                            <td>{{ $key }}
                                @if (!empty($value['description']))
                                    <a href="{{ $settings->get("docs_base") }}{{ $value['description'] }}"
                                       target="_blank">📖</a>
                                @endif
                            </td>
                            <td>
                                @if (is_bool($settings->get($key)))
                                    {{ $settings->get($key) ? "true" : "false" }}
                                @elseif (is_array($settings->get($key)))
                                    {{ json_encode($settings->get($key)) }}
                                @else
                                    {{ $settings->get($key) }}
                                @endif
                            </td>
                            <td>{{ $settings->source($key) }}</td>
                            <td>
                                @if (is_bool($value['default']))
                                    {{ $value['default'] ? "true" : "false" }}
                                @elseif (is_array($value['default']))
                                    {{ json_encode($value['default']) }}
                                @else
                                    {{ $value['default'] }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('admin.partials.footer')
