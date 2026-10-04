@php
    $programsByInstitute = $programsByInstitute ?? [];
    $selectedInstitute = old('institute', $user->institute);
    $selectedProgram = old('program', $user->program);
@endphp

<section class="profile-form-section">
    {{-- Name remains editable, Microsoft email stays display-only, and gender uses school-configured choices. --}}
    <h2>Personal</h2>
    <div class="profile-form-grid">
        <div class="dashboard-form-field">
            <label for="profile-name">Name</label>
            <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}"
                   maxlength="100" autocomplete="name" required>
            @error('name') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="dashboard-form-field">
            <label for="profile-email">Email</label>
            <input id="profile-email" type="email" value="{{ $user->email }}" autocomplete="email" disabled>
            <span class="profile-field-help">Your email is managed by Microsoft.</span>
        </div>
        <div class="dashboard-form-field">
            <label for="profile-gender">Gender</label>
            <select id="profile-gender" name="gender" required>
                <option value="">Select gender</option>
                @foreach ($genders as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $user->gender) === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
            @error('gender') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
    </div>
</section>

<section class="profile-form-section">
    {{-- Academic options come from config/school.php so validation and the form use the same lists. --}}
    <h2>Academic</h2>
    <div class="profile-form-grid">
        <div class="dashboard-form-field">
            <label for="profile-student-number">Student number</label>
            {{-- SECURITY: Only adviser edits enable student-number submission; the request also rejects student changes. --}}
            @if ($allowStudentNumber)
                <input id="profile-student-number" type="text" name="student_number"
                       value="{{ old('student_number', $user->student_number) }}" maxlength="20" required>
            @else
                <input id="profile-student-number" type="text"
                       value="{{ old('student_number', $user->student_number) }}" disabled>
                <span class="profile-field-help">Only an adviser can change your student number.</span>
            @endif
            @error('student_number') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="dashboard-form-field">
            <label for="profile-institute">Institute</label>
            <select id="profile-institute" name="institute" required>
                <option value="">Select institute</option>
                @foreach ($institutes as $institute)
                    <option value="{{ $institute }}" @selected($selectedInstitute === $institute)>{{ $institute }}</option>
                @endforeach
            </select>
            @error('institute') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="dashboard-form-field">
            <label for="profile-program">Program</label>
            <select id="profile-program" name="program" required>
                <option value="">Select program</option>
                @foreach ($programs as $program)
                    @php
                        $programInstitutes = [];
                        foreach ($programsByInstitute as $instituteName => $institutePrograms) {
                            if (in_array($program, $institutePrograms, true)) {
                                $programInstitutes[] = $instituteName;
                            }
                        }
                    @endphp
                    <option value="{{ $program }}"
                            data-institutes="{{ implode('|', $programInstitutes) }}"
                            @selected($selectedProgram === $program)>
                        {{ $program }}
                    </option>
                @endforeach
            </select>
            @error('program') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="dashboard-form-field">
            <label for="profile-year-level">Year level</label>
            <select id="profile-year-level" name="year_level" required>
                <option value="">Select year level</option>
                @foreach ($yearLevels as $yearLevel)
                    <option value="{{ $yearLevel }}" @selected(old('year_level', $user->year_level) === $yearLevel)>
                        {{ $yearLevel }}
                    </option>
                @endforeach
            </select>
            @error('year_level') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
    </div>
    {{-- TODO: The school option lists must be filled before students can pass server-side option validation. --}}
    @if ($institutes === [] || $programs === [])
        <p class="profile-config-notice">
            TODO: Add the official institute and program options in <code>config/school.php</code> before profile completion.
        </p>
    @endif
</section>

<section class="profile-form-section">
    {{-- Contact validation and storage normalize accepted PH mobile numbers on the server. --}}
    <h2>Contact</h2>
    <div class="profile-form-grid">
        <div class="dashboard-form-field">
            <label for="profile-contact-number">Contact number</label>
            <input id="profile-contact-number" type="tel" name="contact_number"
                   value="{{ old('contact_number', $user->contact_number) }}"
                   pattern="(?:09[0-9]{9}|\+639[0-9]{9})"
                   placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                   autocomplete="tel" required>
            @error('contact_number') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
        <div class="dashboard-form-field profile-address-field">
            <label for="profile-address">Address</label>
            <input id="profile-address" type="text" name="address" value="{{ old('address', $user->address) }}"
                   maxlength="255" autocomplete="street-address" required>
            @error('address') <span class="profile-field-error">{{ $message }}</span> @enderror
        </div>
    </div>
</section>

{{-- Officer assignments are managed by advisers, so users can only inspect this value here. --}}
@if ($user->officer_position)
    <section class="profile-form-section">
        <h2>Officer information</h2>
        <div class="dashboard-form-field profile-officer-position">
            <label for="profile-officer-position">Officer position</label>
            <input id="profile-officer-position" type="text" value="{{ $user->officer_position }}" disabled>
        </div>
    </section>
@endif

@push('scripts')
    <script>
        const instituteSelect = document.getElementById('profile-institute');
        const programSelect = document.getElementById('profile-program');

        // Filter mapped programs in the browser; the FormRequest independently validates the selection.
        if (instituteSelect && programSelect) {
            const filterPrograms = () => {
                const institute = instituteSelect.value;

                Array.from(programSelect.options).forEach((option) => {
                    if (!option.value || !option.dataset.institutes) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = !option.dataset.institutes.split('|').includes(institute);
                });

                if (programSelect.selectedOptions[0]?.hidden) {
                    programSelect.value = '';
                }
            };

            instituteSelect.addEventListener('change', filterPrograms);
            filterPrograms();
        }
    </script>
@endpush
