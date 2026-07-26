<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ themeAsset('css/style.css') }}">
    <title>{{ __('BNPL Checkout') . ' - ' . setting('site_title') }}</title>
    <style>
        .cancel-link {
            display: inline-block;
            margin-top: 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
        }

        .action-block {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .feedback-error {
            margin-top: 1rem
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid rgb(255 59 0 / 20%);
            background: rgb(255 0 0 / 10%);
            color: #072126;
            font-size: 1rem;
        }
    </style>
</head>

<body class="bnpl-page">
    <main class="bnpl-shell">
        <form method="POST" action="{{ route('bnpl.confirm') }}" id="bnpl-form">
            @csrf

            @php
                $selectedSplitId = old('split_id', data_get($splitPreviews, '0.split_id'));
            @endphp

            <div class="bnpl-container">
                <section class="bnpl-main">
                    <header class="bnpl-header">
                        <h1>{{ __('BNPL Checkout') }}</h1>
                        <p>{{ __('Order from WordPress received. Review details, choose your split, and confirm.') }}
                        </p>
                    </header>

                    <div class="balance-card">
                        <span>{{ __('Your Balance:') }}</span>
                        <strong>{{ number_format($userBalance, 2) }} {{ $currency }}</strong>
                    </div>

                    <div class="merchant-reference">
                        <div class="box">
                            <h4>{{ __('Merchant Order') }}</h4>
                            <p>{{ data_get($payload, 'merchant_order_id', '-') }}</p>
                        </div>
                        <div class="box">
                            <h4>{{ __('Reference') }}</h4>
                            <p>{{ data_get($payload, 'merchant_reference_id', '-') }}</p>
                        </div>
                        <div class="box">
                            <h4>{{ __('Total Amount') }}</h4>
                            <p>{{ number_format($amount, 2) }} {{ $currency }}</p>
                        </div>
                    </div>

                    <div class="merchant-details">
                        <div class="details-box">
                            <h5>{{ __('Name') }}</h5>
                            <p>{{ data_get($payload, 'customer.name', '-') }}</p>
                        </div>
                        <div class="details-box">
                            <h5>{{ __('Email') }}</h5>
                            <p>{{ data_get($payload, 'customer.email', '-') }}</p>
                        </div>
                        <div class="details-box">
                            <h5>{{ __('Phone') }}</h5>
                            <p>{{ data_get($payload, 'customer.phone', '-') }}</p>
                        </div>
                    </div>

                    <section class="plan-panel">
                        <div class="section-title">{{ __('Select Split Option') }}</div>

                        <div class="plan-grid" id="split-options">
                            @forelse ($splitPreviews as $index => $split)
                                @php
                                    $isSelected = (string) $selectedSplitId === (string) $split['split_id'];
                                @endphp
                                <label class="installment-card {{ $isSelected ? 'is-active' : '' }}">
                                    <input type="radio" name="split_id" value="{{ $split['split_id'] }}"
                                        data-preview='@json($split)' {{ $isSelected ? 'checked' : '' }}
                                        style="position:absolute;opacity:0;pointer-events:none;">
                                    <strong>{{ __(':count installments', ['count' => $split['split_count']]) }}</strong>
                                    <div class="des">
                                        <small>{{ __('Total payable:') }}
                                            {{ number_format((float) $split['total_payable'], 2) }}
                                            {{ $currency }}</small>
                                        <small>{{ __('Initial deduction:') }}
                                            {{ number_format((float) $split['initial_paid_amount'], 2) }}
                                            {{ $currency }}</small>
                                    </div>
                                </label>
                            @empty
                                <div class="details-box">
                                    <p>{{ __('No split options are available right now.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        @error('split_id')
                            <div class="feedback-error">{{ $message }}</div>
                        @enderror

                        @if ($errors->has('payment'))
                            <div class="feedback-error">{{ $errors->first('payment') }}</div>
                        @endif

                        <div class="info-note">
                            <span class="info-note-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 20 20" fill="none">
                                    <g clip-path="url(#clip0_1874_1143)">
                                        <path
                                            d="M11.2513 5.41577C11.2513 5.74729 11.1196 6.06523 10.8851 6.29965C10.6507 6.53407 10.3328 6.66577 10.0013 6.66577C9.66975 6.66577 9.3518 6.53407 9.11738 6.29965C8.88296 6.06523 8.75127 5.74729 8.75127 5.41577C8.75127 5.08425 8.88296 4.7663 9.11738 4.53188C9.3518 4.29746 9.66975 4.16577 10.0013 4.16577C10.3328 4.16577 10.6507 4.29746 10.8851 4.53188C11.1196 4.7663 11.2513 5.08425 11.2513 5.41577ZM20.0013 15.8324V10.2824C20.0324 7.71364 19.0905 5.22818 17.3649 3.32508C15.6392 1.42197 13.2575 0.242131 10.6979 0.022433C9.26931 -0.077519 7.83588 0.130549 6.49463 0.63256C5.15338 1.13457 3.9356 1.91881 2.92369 2.93222C1.91178 3.94563 1.12935 5.16457 0.629327 6.50656C0.129305 7.84855 -0.076638 9.28229 0.0254317 10.7108C0.393765 16.0058 5.0696 19.9991 10.9038 19.9991H15.8346C16.9393 19.9978 17.9983 19.5584 18.7794 18.7773C19.5605 17.9961 19.9999 16.9371 20.0013 15.8324ZM10.5846 1.68577C12.7233 1.87496 14.7112 2.86653 16.1488 4.46123C17.5865 6.05593 18.3674 8.13562 18.3346 10.2824V15.8324C18.3346 16.4955 18.0712 17.1314 17.6024 17.6002C17.1335 18.069 16.4976 18.3324 15.8346 18.3324H10.9038C5.87543 18.3324 2.00127 15.0824 1.68877 10.5958C1.60673 9.45292 1.76124 8.30543 2.14267 7.22499C2.52409 6.14455 3.12423 5.15437 3.90558 4.31633C4.68693 3.47828 5.6327 2.81036 6.68382 2.35431C7.73494 1.89825 8.86881 1.66386 10.0146 1.66577C10.2038 1.66577 10.3946 1.67327 10.5846 1.68577ZM11.6679 14.9991V9.9991C11.6679 9.55707 11.4923 9.13315 11.1798 8.82059C10.8672 8.50803 10.4433 8.33243 10.0013 8.33243H9.16793C8.94692 8.33243 8.73496 8.42023 8.57868 8.57651C8.4224 8.73279 8.3346 8.94475 8.3346 9.16577C8.3346 9.38678 8.4224 9.59874 8.57868 9.75502C8.73496 9.9113 8.94692 9.9991 9.16793 9.9991H10.0013V14.9991C10.0013 15.2201 10.0891 15.4321 10.2453 15.5884C10.4016 15.7446 10.6136 15.8324 10.8346 15.8324C11.0556 15.8324 11.2676 15.7446 11.4239 15.5884C11.5801 15.4321 11.6679 15.2201 11.6679 14.9991Z"
                                            fill="#FFAA00" />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_1874_1143">
                                            <rect width="20" height="20" fill="white" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </span>
                            <p>{{ __('After confirm, initial installment is deducted and confirmation is sent to merchant.') }}
                            </p>
                        </div>

                        <div class="schedule-table-wrap">
                            <table class="schedule-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Due Date') }}</th>
                                        <th>{{ __('Principal') }}</th>
                                        <th>{{ __('Interest') }}</th>
                                        <th>{{ __('Total Due') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="installments-body"></tbody>
                            </table>
                        </div>
                    </section>
                </section>

                <aside class="summary-card">
                    <div class="summary-block">
                        <h2>{{ __('Order Summary') }}</h2>

                        <div class="summary-product-list">
                            @forelse ((array) data_get($payload, 'items', []) as $item)
                                <article class="summary-product">
                                    <div>
                                        <strong>{{ data_get($item, 'name', '-') }}</strong>
                                        <span>{{ __('SKU') }}: {{ data_get($item, 'sku', '-') }} |
                                            {{ __('QTY') }}:
                                            {{ (int) data_get($item, 'quantity', 0) }} | {{ __('Unit') }}:
                                            {{ number_format((float) data_get($item, 'unit_price', 0), 2) }}
                                            {{ $currency }}</span>
                                    </div>
                                    <b>{{ number_format((float) data_get($item, 'line_total', 0), 2) }}
                                        {{ $currency }}</b>
                                </article>
                            @empty
                                <article class="summary-product">
                                    <div>
                                        <strong>{{ __('No items found in payload.') }}</strong>
                                    </div>
                                </article>
                            @endforelse
                        </div>
                    </div>

                    <div class="summary-block">
                        <h3>{{ __('Installment Summary') }}</h3>

                        <div class="summary-metrics">
                            <div class="summary-row">
                                <span>{{ __('Total installments') }}</span>
                                <strong id="sum-count">-</strong>
                            </div>
                            <div class="summary-row">
                                <span>{{ __('Initial installment (deduct now)') }}</span>
                                <strong id="sum-initial">-</strong>
                            </div>
                            <div class="summary-row">
                                <span>{{ __('Remaining financed') }}</span>
                                <strong id="sum-financed">-</strong>
                            </div>
                            <div class="summary-row">
                                <span>{{ __('Total fees') }}</span>
                                <strong id="sum-fees">-</strong>
                            </div>
                            <div class="summary-row">
                                <span>{{ __('Total payable') }}</span>
                                <strong id="sum-payable">-</strong>
                            </div>
                            <div class="summary-row summary-row-total">
                                <span>{{ __('Total') }}</span>
                                <strong id="sum-total">-</strong>
                            </div>
                        </div>
                    </div>

                    <div class="action-block">
                        <button class="submit-btn summary-submit" type="submit">{{ __('Confirm BNPL') }}</button>
                        <a href="{{ route('bnpl.cancel') }}" class="cancel-link">{{ __('Cancel') }}</a>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <script>
        (function() {
            const currency = @json($currency);
            const noScheduleText = @json(__('No installment schedule available.'));
            const paidUpfrontText = @json(__('Paid (upfront)'));
            const pendingText = @json(__('Pending'));
            const radios = document.querySelectorAll('input[name="split_id"]');
            const splitCards = document.querySelectorAll('.installment-card');

            const sumCount = document.getElementById('sum-count');
            const sumInitial = document.getElementById('sum-initial');
            const sumFinanced = document.getElementById('sum-financed');
            const sumFees = document.getElementById('sum-fees');
            const sumPayable = document.getElementById('sum-payable');
            const sumTotal = document.getElementById('sum-total');
            const installmentsBody = document.getElementById('installments-body');

            const amount = (num) => `${Number(num || 0).toFixed(2)} ${currency}`;

            const renderPreview = (preview) => {
                sumCount.textContent = preview.split_count || '-';
                sumInitial.textContent = amount(preview.initial_paid_amount);
                sumFinanced.textContent = amount(preview.final_amount_to_pay);
                sumFees.textContent = amount(preview.total_fees);
                sumPayable.textContent = amount(preview.total_payable);
                sumTotal.textContent = amount(preview.total_payable);

                installmentsBody.innerHTML = '';

                if (!Array.isArray(preview.installments) || !preview.installments.length) {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td colspan="6">${noScheduleText}</td>`;
                    installmentsBody.appendChild(row);
                    return;
                }

                preview.installments.forEach((ins) => {
                    const tr = document.createElement('tr');
                    const isUpfront = Boolean(ins.is_upfront);
                    const rawStatus = String(ins.status || '').toLowerCase();
                    const isPaid = isUpfront || rawStatus === 'paid' || rawStatus === 'success';
                    const badgeClass = isPaid ? 'status-success' : 'status-pending';
                    const statusText = isUpfront ? paidUpfrontText : (ins.status || pendingText);

                    tr.innerHTML = `
                        <td>${ins.installment_no || '-'}</td>
                        <td>${ins.display_due_date || '-'}</td>
                        <td>${amount(ins.principal_amount)}</td>
                        <td>${amount(ins.interest_amount)}</td>
                        <td>${amount(ins.total_due_amount)}</td>
                        <td>
                            <span class="status-badge ${badgeClass}">
                                <span class="status-icon">
                                    <span class="success-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                            <g clip-path="url(#clip0_1874_1175)">
                                                <path d="M11.9979 6.00089C11.9979 9.31401 9.31206 11.9998 5.99894 11.9998C2.68582 11.9998 0 9.31401 0 6.00089C0 2.68777 2.68582 0.00195312 5.99894 0.00195312C9.31206 0.00195312 11.9979 2.68777 11.9979 6.00089Z" fill="#072126" fill-opacity="0.4" />
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.71254 8.64375L7.52657 6.82971L8.17422 6.18207L9.75704 4.59925C10.0985 4.25781 10.0985 3.69913 9.75704 3.35762C9.41559 3.01624 8.85685 3.01618 8.51541 3.35762L6.93259 4.94044L6.92106 4.95197L6.88614 4.98689L6.84994 5.02309L6.81231 5.06072L6.77314 5.09988L6.73249 5.14054L6.69017 5.18286L6.64623 5.2268L6.60043 5.2726L6.55288 5.32015L6.5034 5.36963L6.45193 5.4211L6.39839 5.47464L6.34273 5.5303L6.28494 5.58808L6.28404 5.58905L5.09176 6.78133L3.48208 5.17165C3.14064 4.83021 2.58189 4.83027 2.24051 5.17172C1.89901 5.51316 1.89901 6.07184 2.24045 6.41328L4.47091 8.64375L4.48708 8.65953L4.50358 8.67473L4.52033 8.68949L4.5374 8.70379L4.55473 8.71751L4.57232 8.73078L4.59023 8.74354L4.60833 8.75578L4.62669 8.76757L4.64524 8.77878L4.66405 8.78954L4.68306 8.79978L4.70226 8.80957L4.72165 8.81878L4.74123 8.82755L4.76095 8.83579L4.78085 8.84359L4.80089 8.8508L4.82112 8.85757L4.84141 8.86382L4.8619 8.86955L4.88238 8.87483H4.88245L4.90306 8.8796L4.92381 8.88385L4.94462 8.88759L4.96549 8.89081L4.98643 8.89358L5.00743 8.89583L5.0285 8.89757L5.04956 8.8988V8.89886L5.07063 8.89957L5.0917 8.89983H5.09176L5.11283 8.89957L5.13389 8.89886V8.8988L5.15496 8.89757L5.17603 8.89583L5.19696 8.89358H5.19703L5.21797 8.89081L5.23884 8.88759L5.25965 8.88385L5.28039 8.8796L5.30101 8.87483L5.32156 8.86961V8.86955L5.34198 8.86382H5.34204L5.36234 8.85757L5.3825 8.85087L5.38257 8.8508L5.40254 8.84359H5.4026L5.42245 8.83586L5.42251 8.83579L5.44222 8.82761V8.82755L5.46181 8.81885V8.81878L5.4812 8.80957L5.5004 8.79984V8.79978L5.5194 8.7896V8.78954L5.53815 8.77884L5.53821 8.77878L5.55677 8.76757L5.57513 8.75584V8.75578L5.59323 8.7436V8.74354L5.61108 8.73085L5.61114 8.73078L5.62866 8.71758L5.62873 8.71751L5.64599 8.70379H5.64606L5.66307 8.68955L5.66313 8.68949L5.67982 8.6748L5.67988 8.67473L5.69631 8.65959L5.69637 8.65953L5.71248 8.64381L5.71254 8.64375Z" fill="white" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_1874_1175">
                                                    <rect width="12" height="12" fill="white" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </span>
                                    <span class="pending-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                            <g clip-path="url(#clip0_1874_1195)">
                                                <path d="M6.71406 1.01328C6.71406 0.585781 6.42906 0.300781 6.00156 0.300781C5.57406 0.300781 5.28906 0.585781 5.28906 1.01328V2.43828C5.28906 2.86578 5.57406 3.15078 6.00156 3.15078C6.42906 3.15078 6.71406 2.86578 6.71406 2.43828V1.01328Z" fill="white" />
                                                <path d="M6.00156 8.84961C5.57406 8.84961 5.28906 9.13461 5.28906 9.56211V10.9871C5.28906 11.4146 5.57406 11.6996 6.00156 11.6996C6.42906 11.6996 6.71406 11.4146 6.71406 10.9871V9.56211C6.71406 9.13461 6.42906 8.84961 6.00156 8.84961Z" fill="white" />
                                                <path d="M4.00781 3.00836L3.01031 1.93961C2.72531 1.72586 2.22656 1.72586 1.94156 1.93961C1.72781 2.22461 1.72781 2.72336 1.94156 3.00836L2.93906 4.00586C3.22406 4.29086 3.65156 4.29086 3.93656 4.00586C4.29281 3.72086 4.29281 3.22211 4.00781 3.00836Z" fill="white" />
                                                <path d="M8.9925 10.0623C9.2775 10.3473 9.705 10.3473 9.99 10.0623C10.275 9.77726 10.275 9.34976 9.99 9.06476L8.9925 8.06726C8.7075 7.78227 8.28 7.78227 7.995 8.06726C7.71 8.35226 7.71 8.77976 7.995 9.06476L8.9925 10.0623Z" fill="white" />
                                                <path d="M0.296875 5.99961C0.296875 6.42711 0.581875 6.71211 1.00938 6.71211H2.43438C2.86187 6.71211 3.14688 6.42711 3.14688 5.99961C3.14688 5.57211 2.86187 5.28711 2.43438 5.28711H1.00938C0.581875 5.28711 0.296875 5.57211 0.296875 5.99961Z" fill="white" />
                                                <path d="M11.7016 5.99961C11.7016 5.57211 11.4166 5.28711 10.9891 5.28711H9.56406C9.13656 5.28711 8.85156 5.57211 8.85156 5.99961C8.85156 6.42711 9.13656 6.71211 9.56406 6.71211H10.9891C11.4166 6.71211 11.7016 6.42711 11.7016 5.99961Z" fill="white" />
                                                <path d="M3.00813 10.0623L4.00563 9.06476C4.29063 8.77976 4.29063 8.35226 4.00563 8.06726C3.72063 7.78227 3.29313 7.78227 3.00813 8.06726L2.01063 9.06476C1.72562 9.34976 1.72562 9.77727 2.01063 10.0623C2.22438 10.276 2.72313 10.276 3.00813 10.0623Z" fill="white" />
                                                <path d="M8.9925 1.93961L7.995 2.93711C7.71 3.22211 7.71 3.64961 7.995 3.93461C8.28 4.21961 8.7075 4.21961 8.9925 3.93461L9.99 2.93711C10.275 2.65211 10.275 2.22461 9.99 1.93961C9.77625 1.72586 9.2775 1.72586 8.9925 1.93961Z" fill="white" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_1874_1195">
                                                    <rect width="12" height="12" fill="white" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </span>
                                </span>
                                ${statusText}
                            </span>
                        </td>
                    `;

                    installmentsBody.appendChild(tr);
                });
            };

            const activateCard = (targetRadio) => {
                splitCards.forEach((card) => card.classList.remove('is-active'));
                const card = targetRadio.closest('.installment-card');
                if (card) {
                    card.classList.add('is-active');
                }
            };

            radios.forEach((radio) => {
                radio.addEventListener('change', function() {
                    let preview = {};

                    try {
                        preview = JSON.parse(this.dataset.preview || '{}');
                    } catch (error) {
                        preview = {};
                    }

                    activateCard(this);
                    renderPreview(preview);
                });
            });

            const initiallySelected = document.querySelector('input[name="split_id"]:checked');
            if (initiallySelected) {
                let initialPreview = {};

                try {
                    initialPreview = JSON.parse(initiallySelected.dataset.preview || '{}');
                } catch (error) {
                    initialPreview = {};
                }

                renderPreview(initialPreview);
            }
        })();
    </script>
</body>

</html>
