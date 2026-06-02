<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Jobs\ProcessUploadedImage;
use App\Models\ScheduleSlot;
use App\Models\School;
use App\Models\SchoolJoinRequest;
use App\Models\SchoolSlotTime;
use App\Models\Subject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        $approvedSchoolIds = $user->schools()->pluck('schools.id');
        $requestedSchoolIds = SchoolJoinRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('school_id');
        $excludedIds = $approvedSchoolIds->merge($requestedSchoolIds)->unique();

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'assignedLessons' => $user->lessons()
                ->with([
                    'group:id,slug,grade,name,school_id',
                    'group.school:id,name',
                    'subject:id,name',
                ])
                ->get(['lessons.id', 'lessons.group_id', 'lessons.subject_id', 'lessons.lm_level']),
            'allSubjects' => Subject::orderBy('name')->get(['id', 'name']),
            'userSubjectIds' => $user->subjects()->pluck('subjects.id'),
            'pendingRequests' => SchoolJoinRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->with('school:id,name')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'school' => ['id' => $r->school->id, 'name' => $r->school->name]]),
            'availableSchools' => School::orderBy('name')
                ->whereNotIn('id', $excludedIds)
                ->get(['id', 'name']),
            'scheduleSlots' => ScheduleSlot::orderBy('position')
                ->get(['id', 'label', 'type', 'start_time', 'end_time'])
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'label' => $s->label,
                    'type' => $s->type->value,
                    'start_time' => $s->start_time ? substr($s->start_time, 0, 5) : null,
                    'end_time' => $s->end_time ? substr($s->end_time, 0, 5) : null,
                ]),
            'adminSchoolSlotTimes' => SchoolSlotTime::whereIn(
                'school_id',
                $user->schools()->wherePivot('role', 'admin')->pluck('schools.id'),
            )
                ->get()
                ->groupBy('school_id')
                ->map(fn ($rows) => $rows->keyBy('schedule_slot_id')->map(fn ($r) => [
                    'start_time' => substr($r->start_time, 0, 5),
                    'end_time' => substr($r->end_time, 0, 5),
                ])),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except('picture'));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('picture')) {
            $disk = config('images.disk');

            if ($user->picture) {
                Storage::disk($disk)->delete(config('images.original_path').'/'.$user->picture);
            }

            $filename = Str::uuid().'.webp';
            $originalPath = Storage::disk($disk)->putFileAs(
                config('images.original_path'),
                $request->file('picture'),
                $filename
            );

            if ($originalPath) {
                $user->picture = $filename;
                ProcessUploadedImage::dispatchSync($originalPath, $filename, 'images');
            }
        }

        $user->save();

        return to_route('profile.edit');
    }

    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
