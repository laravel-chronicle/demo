<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Cleans up artifacts created by the Integrity Lab panels so each panel can be
 * reset to its pre-run state. Checkpoints are immutable to Eloquent, so they are
 * removed with the raw query builder after their entry FK stamps are cleared.
 */
class LabSandbox
{
    /**
     * @param  list<string>  $checkpointIds
     */
    public function forgetCheckpoints(array $checkpointIds): void
    {
        if ($checkpointIds === []) {
            return;
        }

        DB::table('chronicle_entries')
            ->whereIn('checkpoint_id', $checkpointIds)
            ->update(['checkpoint_id' => null]);

        DB::table('chronicle_checkpoints')
            ->whereIn('id', $checkpointIds)
            ->delete();
    }

    /**
     * Remove an artifact at the given path. Export bundles are directories
     * (entries.ndjson + manifest.json + signature.json); compliance reports are
     * single files. Both are handled, and a missing path is a no-op.
     */
    public function deletePath(?string $path): void
    {
        if ($path === null) {
            return;
        }

        if (File::isDirectory($path)) {
            File::deleteDirectory($path);

            return;
        }

        File::delete($path);
    }

    /**
     * Delete external-anchor receipt rows for the given checkpoints. Used by the
     * full-compromise panel so its TSA-anchored checkpoint resets cleanly.
     *
     * @param  list<string>  $checkpointIds
     */
    public function forgetAnchors(array $checkpointIds): void
    {
        if ($checkpointIds === []) {
            return;
        }

        DB::table('chronicle_checkpoint_anchors')
            ->whereIn('checkpoint_id', $checkpointIds)
            ->delete();
    }
}
