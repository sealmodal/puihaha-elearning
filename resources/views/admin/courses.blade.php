<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900">Courses &amp; Enrollments</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Lower-tier courses automatically unlock for higher tiers (Basic &rarr; Silver &rarr; Gold &rarr;
                Platinum).
            </p>
        </div>
    </x-slot>

    <div class="py-6 px-5 sm:px-7 space-y-6 w-full">
        @if(session('success'))
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium">
                {{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Add New Course -->
        <form method="POST" action="{{ route('admin.courses.store') }}" class="ph-card p-6 space-y-4">
            @csrf
            <h2 class="text-base font-extrabold text-slate-900">Add New Course</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <input type="text" name="course_code" placeholder="Code (e.g., CRS-501)"
                    class="rounded-xl border-slate-200 text-sm font-mono px-3 py-2" required>
                <input type="text" name="title" placeholder="Course Title"
                    class="rounded-xl border-slate-200 text-sm px-3 py-2" required>
                <input type="text" name="instructor" placeholder="Instructor"
                    class="rounded-xl border-slate-200 text-sm px-3 py-2">
                <input type="number" min="1" name="duration_hours" placeholder="Hours"
                    class="rounded-xl border-slate-200 text-sm font-mono px-3 py-2" required>
            </div>
            <textarea name="description" rows="2" placeholder="Course description or lesson overview..."
                class="w-full rounded-xl border-slate-200 text-sm px-3 py-2"></textarea>
            <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-700">
                    <span class="font-bold">Starting Tier (unlocks for this &amp; higher tiers):</span>
                    @foreach($levels as $level)
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" name="level_ids[]" value="{{ $level->id }}"
                                class="rounded border-slate-300 text-blue-600">
                            <span>{{ $level->name }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="ph-btn-primary">
                    Add Course
                </button>
            </div>
        </form>

        <!-- Existing Courses List + Edit Form -->
        <div class="ph-grid-2">
            @foreach($courses as $course)
                @php $checkedIds = $course->allowedLevels->pluck('id')->all(); @endphp
                <div x-data="{ editing: false }" class="ph-card p-6 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span
                                class="font-mono text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">{{ $course->course_code }}</span>
                            <span class="text-xs text-slate-500 ml-2 font-mono">{{ $course->duration_hours }} hrs</span>
                            <span
                                class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $course->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $course->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <h3 class="font-bold text-base sm:text-lg text-slate-900 mt-1.5">{{ $course->title }}</h3>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="editing = !editing"
                                class="px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 text-xs font-semibold hover:bg-slate-100 cursor-pointer">
                                <span x-text="editing ? 'Close' : 'Edit'"></span>
                            </button>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                onsubmit="return confirm('Delete this course?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ph-btn-danger-outline">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    <p class="text-xs sm:text-sm text-slate-600">{{ $course->description }}</p>

                    <div class="text-xs text-slate-500">
                        Assigned Tiers: <strong
                            class="text-slate-700">{{ $course->allowedLevels->isEmpty() ? 'All Levels' : $course->allowedLevels->pluck('name')->join(', ') }}</strong>
                    </div>

                    <!-- Edit Course Form -->
                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.courses.update', $course) }}"
                        class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input type="text" name="course_code" value="{{ old('course_code', $course->course_code) }}"
                                placeholder="Code"
                                class="rounded-lg border-slate-200 text-xs font-mono px-2.5 py-1.5 bg-white" required>
                            <input type="number" min="1" name="duration_hours"
                                value="{{ old('duration_hours', $course->duration_hours) }}" placeholder="Hours"
                                class="rounded-lg border-slate-200 text-xs font-mono px-2.5 py-1.5 bg-white" required>
                            <input type="text" name="title" value="{{ old('title', $course->title) }}"
                                placeholder="Course Title"
                                class="rounded-lg border-slate-200 text-xs px-2.5 py-1.5 bg-white" required>
                            <input type="text" name="instructor" value="{{ old('instructor', $course->instructor) }}"
                                placeholder="Instructor" class="rounded-lg border-slate-200 text-xs px-2.5 py-1.5 bg-white">
                        </div>
                        <textarea name="description" rows="2" placeholder="Description..."
                            class="w-full rounded-lg border-slate-200 text-xs px-2.5 py-1.5 bg-white">{{ old('description', $course->description) }}</textarea>
                        <div class="flex flex-wrap gap-3 text-xs text-slate-700">
                            @foreach($levels as $level)
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="level_ids[]" value="{{ $level->id }}"
                                        @checked(in_array($level->id, $checkedIds))
                                        class="rounded border-slate-300 text-blue-600">
                                    <span>{{ $level->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between pt-1">
                            <label
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" @checked($course->is_active)
                                    class="rounded border-slate-300 text-blue-600">
                                <span>Active</span>
                            </label>
                            <button type="submit"
                                class="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 cursor-pointer">
                                Save Course
                            </button>
                        </div>
                    </form>

                    <div class="pt-3 border-t border-slate-100 text-xs">
                        <strong class="text-slate-700">Enrolled
                            ({{ $course->enrollments->where('status', 'enrolled')->count() }}):</strong>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                            @forelse($course->enrollments->where('status', 'enrolled') as $enr)
                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-800 font-medium">
                                    {{ $enr->member->full_name }}
                                </span>
                            @empty
                                <span class="text-slate-400">No active enrollments</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>