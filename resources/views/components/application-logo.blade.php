<svg
    viewBox="0 0 160 160"
    width="160"
    height="160"
    preserveAspectRatio="xMidYMid meet"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    {{ $attributes }}
>
    <title>Workforce Clock</title>
    <defs>
        <linearGradient id="workforceClockBadge" x1="24" y1="20" x2="132" y2="140" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#0f172a"/>
            <stop offset="1" stop-color="#1e3a8a"/>
        </linearGradient>
        <linearGradient id="workforceClockDial" x1="56" y1="48" x2="102" y2="102" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ffffff"/>
            <stop offset="1" stop-color="#dbeafe"/>
        </linearGradient>
    </defs>

    <rect x="14" y="14" width="132" height="132" rx="34" fill="url(#workforceClockBadge)"/>
    <path
        d="M37 104L50 60L69 101L80 78L91 101L110 60L123 104"
        stroke="#f8fafc"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="10"
    />
    <circle cx="109" cy="50" r="24" fill="url(#workforceClockDial)"/>
    <circle cx="109" cy="50" r="20" stroke="#f97316" stroke-width="6"/>
    <path d="M109 38V51H120" stroke="#1d4ed8" stroke-linecap="round" stroke-linejoin="round" stroke-width="6"/>
    <circle cx="109" cy="50" r="4.5" fill="#1d4ed8"/>
    <path d="M43 118H117" stroke="#ffffff" stroke-linecap="round" stroke-opacity="0.2" stroke-width="4"/>
</svg>
