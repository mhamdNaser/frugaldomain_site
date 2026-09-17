<?php

namespace App\Modules\App\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\App\Models\App;
use App\Modules\App\Requests\ProvisionSubdomainRequest;
use App\Modules\App\Resources\AppResource;
use App\Modules\App\Support\AppStorage;
use App\Modules\App\Support\HostingerClient;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Automatic subdomain provisioning for a catalogue app.
 *
 * The flow a "Create subdomain" click triggers:
 *
 *   validate the prefix  ->  create <prefix>.<domain> pointing at the app's
 *   own directory        ->  request SSL (asynchronous)
 *                        ->  persist subdomain + preview_mode/preview_url
 *
 * SSL is never assumed to be live: the response always says whether it is
 * still pending, and GET .../subdomain/status exists so the UI can poll.
 *
 * With no HOSTINGER_API_TOKEN set nothing here errors — every endpoint
 * answers with configured:false and a clear message, so the dashboard keeps
 * working on a machine that has no Hostinger credentials.
 */
class AppSubdomainController extends Controller
{
    // Reuses the module's single path-traversal guard rather than repeating it.
    use AppStorage;

    protected HostingerClient $hostinger;

    public function __construct(HostingerClient $hostinger)
    {
        $this->hostinger = $hostinger;
    }

    /**
     * POST /api/admin/apps/{id}/subdomain
     *
     * Body: { "prefix": "my-project" }
     */
    public function store(ProvisionSubdomainRequest $request, $id)
    {
        $app = App::with(['features', 'images'])->findOrFail((int) $id);
        $prefix = (string) $request->validated()['prefix'];

        $check = $this->hostinger->validatePrefix($prefix);
        if (!$check['ok']) {
            throw ValidationException::withMessages(['prefix' => $check['message']]);
        }
        $prefix = $check['prefix'];

        if (!$this->hostinger->configured()) {
            return response()->json([
                'success' => false,
                'configured' => false,
                'message' => $this->hostinger->notConfiguredMessage(),
                'data' => new AppResource($app),
                'subdomain' => $this->state($app),
            ], 503);
        }

        // Another app must not claim a prefix this one already owns.
        $taken = App::where('subdomain', $prefix)
            ->where('id', '!=', $app->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'prefix' => "The subdomain \"{$prefix}\" is already assigned to another project.",
            ]);
        }

        // Directory relative to the website root, e.g. "apps/my-project".
        // appDirectory() rejects anything that is not a strict slug, so the
        // value handed to Hostinger can never contain "..", "/" or "\".
        try {
            $directory = $this->appDirectory($app->slug);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['slug' => $e->getMessage()]);
        }

        $created = $this->hostinger->createSubdomain($prefix, $directory);

        // A 422 usually means "already exists". If the zone genuinely already
        // carries this label, treat it as success and carry on to SSL rather
        // than stranding the admin with an error they cannot clear.
        if (!$created['ok']) {
            $alreadyThere = ($created['status'] === 422)
                && $this->hostinger->subdomainExists($prefix) === true;

            if (!$alreadyThere) {
                return response()->json([
                    'success' => false,
                    'configured' => true,
                    'message' => $created['message'],
                    'data' => new AppResource($app),
                    'subdomain' => $this->state($app),
                ], $created['status'] === 422 ? 422 : 502);
            }
        }

        $fqdn = $this->hostinger->fqdn($prefix);

        // SSL issuance is asynchronous; a failure here must not undo a
        // subdomain that was created successfully.
        $ssl = $this->hostinger->installSsl($fqdn);
        $sslLive = $ssl['ok'] ? $this->hostinger->sslIsActive($ssl['data']) : false;

        $app->forceFill([
            'subdomain' => $prefix,
            'preview_mode' => 'subdomain',
            'preview_url' => 'https://' . $fqdn,
        ])->save();

        $app = $app->fresh(['features', 'images']);

        return response()->json([
            'success' => true,
            'configured' => true,
            'message' => $sslLive
                ? 'Subdomain created and SSL is active.'
                : 'Subdomain created. SSL was requested and is still being issued.',
            'data' => new AppResource($app),
            'subdomain' => $this->state($app, [
                'ssl_active' => $sslLive,
                'ssl_pending' => !$sslLive,
                'ssl_message' => $ssl['ok'] ? '' : $ssl['message'],
            ]),
        ], 201);
    }

    /**
     * GET /api/admin/apps/{id}/subdomain/status
     *
     * Cheap enough to poll: one SSL status call, or none at all when the app
     * has no subdomain yet.
     */
    public function status($id)
    {
        $app = App::findOrFail((int) $id);

        if (!$app->subdomain) {
            return response()->json([
                'success' => true,
                'configured' => $this->hostinger->configured(),
                'message' => 'No subdomain has been provisioned for this project.',
                'subdomain' => $this->state($app),
            ]);
        }

        if (!$this->hostinger->configured()) {
            return response()->json([
                'success' => true,
                'configured' => false,
                'message' => $this->hostinger->notConfiguredMessage(),
                'subdomain' => $this->state($app, ['ssl_pending' => false]),
            ]);
        }

        $fqdn = $this->hostinger->fqdn($app->subdomain);
        $ssl = $this->hostinger->sslStatus($fqdn);
        $sslLive = $ssl['ok'] ? $this->hostinger->sslIsActive($ssl['data']) : false;

        return response()->json([
            'success' => true,
            'configured' => true,
            'message' => $sslLive
                ? 'SSL is active — the subdomain is live over HTTPS.'
                : ($ssl['ok']
                    ? 'SSL is still being issued.'
                    : $ssl['message']),
            'subdomain' => $this->state($app, [
                'ssl_active' => $sslLive,
                'ssl_pending' => !$sslLive,
                'ssl_message' => $ssl['ok'] ? '' : $ssl['message'],
                // A transport/auth failure is not the same as "still issuing";
                // the UI stops polling on this rather than looping forever.
                'ssl_error' => !$ssl['ok'],
            ]),
        ]);
    }

    /**
     * DELETE /api/admin/apps/{id}/subdomain
     *
     * Removes the subdomain at Hostinger and clears the columns. The columns
     * are cleared even when the remote delete reports "not found", so the
     * dashboard can never be left pointing at a subdomain that is gone.
     */
    public function destroy($id)
    {
        $app = App::with(['features', 'images'])->findOrFail((int) $id);
        $prefix = (string) $app->subdomain;

        if ($prefix === '') {
            return response()->json([
                'success' => true,
                'configured' => $this->hostinger->configured(),
                'message' => 'This project has no subdomain to remove.',
                'data' => new AppResource($app),
                'subdomain' => $this->state($app),
            ]);
        }

        $remoteMessage = '';

        if ($this->hostinger->configured()) {
            $deleted = $this->hostinger->deleteSubdomain($prefix);

            if (!$deleted['ok'] && $deleted['status'] !== 404) {
                return response()->json([
                    'success' => false,
                    'configured' => true,
                    'message' => $deleted['message'],
                    'data' => new AppResource($app),
                    'subdomain' => $this->state($app),
                ], 502);
            }

            if (!$deleted['ok']) {
                $remoteMessage = ' Hostinger reported it was already gone.';
            }
        } else {
            $remoteMessage = ' ' . $this->hostinger->notConfiguredMessage();
        }

        $app->forceFill([
            'subdomain' => null,
            'preview_mode' => 'subfolder',
            'preview_url' => null,
        ])->save();

        $app = $app->fresh(['features', 'images']);

        return response()->json([
            'success' => true,
            'configured' => $this->hostinger->configured(),
            'message' => 'Subdomain removed.' . $remoteMessage,
            'data' => new AppResource($app),
            'subdomain' => $this->state($app),
        ]);
    }

    /**
     * The structured block every endpoint here returns, so the UI reads one
     * shape regardless of which call produced it.
     */
    protected function state(App $app, array $overrides = []): array
    {
        $prefix = $app->subdomain ? (string) $app->subdomain : null;

        return array_merge([
            'prefix' => $prefix,
            'domain' => $this->hostinger->configuredDomain(),
            'fqdn' => $prefix ? $this->hostinger->fqdn($prefix) : null,
            'url' => $prefix ? $this->hostinger->liveUrl($prefix) : null,
            'configured' => $this->hostinger->configured(),
            'ssl_active' => false,
            'ssl_pending' => false,
            'ssl_message' => '',
            'ssl_error' => false,
        ], $overrides);
    }
}
