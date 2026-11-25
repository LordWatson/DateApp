<?php

namespace App\Actions;

use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Response;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What one person keeps in their own account of the history they share with
 * someone else. It never touches what the other person sees, and it deletes
 * nothing: a choice can be reversed by running it again.
 */
class ApplyConnectionChoicesAction
{
    public function execute(User $user, User $other, bool $keepPlans, bool $keepNotes): void
    {
        DB::transaction(function () use ($user, $other, $keepPlans, $keepNotes): void {
            $planIds = $this->sharedPlans($user, $other)->pluck('id');

            DB::table('date_night_plan_hidden')->where('user_id', $user->id)->whereIn('date_night_plan_id', $planIds)->delete();

            if (! $keepPlans) {
                DB::table('date_night_plan_hidden')->insert($planIds->map(fn (int $id): array => [
                    'date_night_plan_id' => $id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            }

            $hiddenAt = $keepNotes ? null : now();

            LoveNote::where('sender_id', $user->id)->where('recipient_id', $other->id)->update(['hidden_for_sender_at' => $hiddenAt]);
            LoveNote::where('recipient_id', $user->id)->where('sender_id', $other->id)->update(['hidden_for_recipient_at' => $hiddenAt]);
        });
    }

    /**
     * What there is to decide about, and what the person has chosen so far.
     *
     * @return array{plans: int, notes: int, keeping_plans: bool, keeping_notes: bool}
     */
    public function summary(User $user, User $other): array
    {
        $planIds = $this->sharedPlans($user, $other)->pluck('id');
        $notes = LoveNote::query()->where(fn (Builder $query) => $query
            ->where(fn (Builder $q) => $q->where('sender_id', $user->id)->where('recipient_id', $other->id))
            ->orWhere(fn (Builder $q) => $q->where('recipient_id', $user->id)->where('sender_id', $other->id)));

        return [
            'plans' => $planIds->count(),
            'notes' => (clone $notes)->count(),
            'keeping_plans' => ! DB::table('date_night_plan_hidden')->where('user_id', $user->id)->whereIn('date_night_plan_id', $planIds)->exists(),
            'keeping_notes' => (clone $notes)->visibleTo($user)->count() === (clone $notes)->count(),
        ];
    }

    /**
     * Plans this user can see that were built on the other person's answers:
     * the ones they made together, and the other person's solo plans shared
     * with them. The user's own solo plans are theirs and never included.
     *
     * @return Builder<DateNightPlan>
     */
    private function sharedPlans(User $user, User $other): Builder
    {
        $mine = Response::where('user_id', $user->id)->select('id');
        $theirs = Response::where('user_id', $other->id)->select('id');

        return DateNightPlan::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('partner_one_response_id', $mine)
                ->orWhereIn('partner_two_response_id', $mine)
                ->orWhere('partner_user_id', $user->id))
            ->where(fn (Builder $query) => $query
                ->whereIn('partner_one_response_id', $theirs)
                ->orWhereIn('partner_two_response_id', $theirs));
    }
}
