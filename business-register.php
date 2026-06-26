<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน ผู้ประกอบการ - Pawlands</title>
    
    <!-- Google Fonts - Kanit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="business_register.css">
    <style>
        /* Critical step visibility — inline to ensure it always loads */
        .step-form {
            display: none !important;
        }
        .step-form.active {
            display: flex !important;
            flex-direction: column;
            gap: 16px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="registration-card">
            
            <!-- Logo -->
            <div class="logo-container">
                <img src="logo.png" alt="Logo" class="logo">
            </div>

            <!-- Title -->
            <h1 class="title">ลงทะเบียน ผู้ประกอบการ</h1>

            <!-- Error/Success Message -->
            <div id="messageBox" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center;"></div>

            <!-- ========== STEP 1: Personal Info ========== -->
            <form id="step1" class="step-form active">
                <div class="form-group">
                    <input type="text" id="firstName" class="form-input" placeholder="ชื่อ" required>
                </div>

                <div class="form-group">
                    <input type="text" id="lastName" class="form-input" placeholder="นามสกุล" required>
                </div>

                <div class="form-group">
                    <input type="email" id="email" class="form-input" placeholder="อีเมล" required>
                </div>

                <div class="form-group">
                    <label class="input-label">ตั้งรหัสผ่าน</label>
                    <div class="password-wrapper">
                        <input type="password" id="password1" class="form-input" placeholder="รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password1')">
                            <span class="iconify eye-icon" data-icon="mdi:eye-off-outline" data-width="22"></span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label">ยืนยันรหัสผ่าน</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword1" class="form-input" placeholder="รหัสผ่าน" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword1')">
                            <span class="iconify eye-icon" data-icon="mdi:eye-off-outline" data-width="22"></span>
                        </button>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-primary" onclick="validateStep1()">ถัดไป</button>
                </div>
            </form>

            <!-- ========== STEP 2: Business Info ========== -->
            <form id="step2" class="step-form">
                <div class="form-group">
                    <input type="text" id="businessName" class="form-input" placeholder="ชื่อสถานประกอบการ" required>
                </div>

                <!-- Business Type Checkboxes -->
                <div class="form-group">
                    <label style="font-family:'Kanit',sans-serif;font-size:14px;font-weight:600;color:#374151;display:block;margin-bottom:8px;">
                        ประเภทธุรกิจ <span style="font-weight:400;font-size:12px;color:#64748b;">(เลือกได้หลายประเภท)</span>
                    </label>
                    <div id="bizTypePills" style="display:flex;flex-wrap:wrap;gap:10px;">
                        <label class="biz-cat-pill" style="display:flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;transition:all .15s;" onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" value="โรงแรม" name="biz_type_cb" style="display:none" onchange="onBizTypeChange()">
                            <span class="iconify" data-icon="fa6-solid:hotel" data-width="16"></span> โรงแรม
                        </label>
                        <label class="biz-cat-pill" style="display:flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;transition:all .15s;" onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" value="คาเฟ่" name="biz_type_cb" style="display:none" onchange="onBizTypeChange()">
                            <span class="iconify" data-icon="carbon:cafe" data-width="16"></span> คาเฟ่
                        </label>
                        <label class="biz-cat-pill" style="display:flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;transition:all .15s;" onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" value="ร้านอาหาร" name="biz_type_cb" style="display:none" onchange="onBizTypeChange()">
                            <span class="iconify" data-icon="material-symbols:restaurant" data-width="16"></span> ร้านอาหาร
                        </label>
                        <label class="biz-cat-pill" style="display:flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;transition:all .15s;" onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" value="อาบน้ำ ตัดขน" name="biz_type_cb" style="display:none" onchange="onBizTypeChange()">
                            <span class="iconify" data-icon="ion:cut" data-width="16"></span> อาบน้ำ ตัดขน
                        </label>
                        <label class="biz-cat-pill" style="display:flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px solid #e2e8f0;border-radius:20px;cursor:pointer;font-family:'Kanit',sans-serif;font-size:13px;color:#374151;user-select:none;transition:all .15s;" onmouseover="this.style.borderColor='#123451'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e2e8f0'">
                            <input type="checkbox" value="โรงพยาบาลสัตว์" name="biz_type_cb" style="display:none" onchange="onBizTypeChange()">
                            <span class="iconify" data-icon="mingcute:hospital-fill" data-width="16"></span> โรงพยาบาลสัตว์
                        </label>
                    </div>
                    <span id="err_bizType" style="display:none;color:#ef4444;font-size:12px;font-family:'Kanit',sans-serif;margin-top:4px;">กรุณาเลือกประเภทธุรกิจอย่างน้อย 1 ประเภท</span>
                </div>

                <!-- Animal Type Dropdown -->
                <div class="form-group">
                    <div class="custom-select" id="animalTypeSelect">
                        <div class="select-selected" onclick="toggleDropdown('animalTypeSelect')">
                            <span class="select-placeholder">ประเภทสัตว์ที่รับ</span>
                            <span class="iconify select-arrow" data-icon="mdi:chevron-down" data-width="22"></span>
                        </div>
                        <div class="select-options">
                            <div class="select-option" onclick="selectAnimalType('สุนัข (หมา)')">สุนัข (หมา)</div>
                            <div class="select-option" onclick="selectAnimalType('แมว')">แมว</div>
                            <div class="select-option" onclick="selectAnimalType('นก')">นก</div>
                            <div class="select-option" onclick="selectAnimalType('exotic pets ทุกประเภท')">exotic pets ทุกประเภท</div>
                            <div class="select-option" onclick="selectAnimalType('exotic pets บางประเภท')">exotic pets บางประเภท (ระบุเพิ่มเติม)</div>
                            <div class="select-option" onclick="selectAnimalType('สุนัข (หมา) และ แมว')">สุนัข (หมา) และ แมว</div>
                            <div class="select-option" onclick="selectAnimalType('รับทุกประเภทที่กำหนดมา')">รับทุกประเภทที่กำหนดมา</div>
                        </div>
                    </div>
                </div>

                <!-- Exotic pets custom input -->
                <div class="form-group" id="exoticCustomGroup" style="display:none;">
                    <input type="text" id="exoticCustomInput" class="form-input" placeholder="ระบุประเภท exotic pets ที่รับ">
                </div>

                <!-- Description textarea -->
                <div class="form-group">
                    <textarea id="businessDetails" class="form-textarea" placeholder="รายละเอียดธุรกิจ" rows="5"></textarea>
                </div>

                <!-- Dynamic Document Upload Section -->
                <div id="documentSection" style="display:none;">
                    <div class="section-label" style="font-weight:600; font-size:14px; margin-bottom:12px; color:#374151;">เอกสารยืนยันการประกอบการ</div>
                    <div id="documentFields"></div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-primary" onclick="validateStep2()">ถัดไป</button>
                </div>
            </form>

            <!-- ========== STEP 3: Location & Details ========== -->
            <form id="step3" class="step-form">
                <div class="form-group">
                    <input type="text" id="address" class="form-input" placeholder="ที่อยู่" required>
                </div>

                <div class="form-group">
                    <input type="text" id="province" class="form-input" placeholder="จังหวัด" required>
                </div>

                <!-- Conditions Section -->
                <div class="conditions-section">
                    <label class="section-label">เงื่อนไขเกี่ยวกับสัตว์เลี้ยง</label>
                    
                    <!-- รับสัตว์เลี้ยงหรือไม่ -->
                    <div class="radio-group">
                        <label class="section-sublabel">รับสัตว์เลี้ยงหรือไม่</label>
                        <div class="radio-options">
                            <label class="radio-option">
                                <input type="radio" name="acceptPets" value="yes" checked>
                                <span class="radio-label">ใช่</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="acceptPets" value="no">
                                <span class="radio-label">ไม่ใช่</span>
                            </label>
                        </div>
                    </div>

                    <!-- ขนาดที่รับ -->
                    <div class="radio-group">
                        <label class="section-sublabel">ขนาดที่รับ</label>
                        <div class="radio-options-vertical">
                            <label class="radio-option">
                                <input type="radio" name="petSize" value="small">
                                <span class="radio-label">รับเฉพาะขนาดเล็ก (≤ 10 กิโลกรัม)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="petSize" value="medium">
                                <span class="radio-label">รับเฉพาะขนาดเล็ก-กลาง (≤ 25 กิโลกรัม)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="petSize" value="all">
                                <span class="radio-label">รับทุกขนาด</span>
                            </label>
                            <label class="radio-option radio-option-with-input">
                                <input type="radio" name="petSize" value="custom">
                                <span class="radio-label">รับสูงสุดไม่เกิน</span>
                                <input type="number" id="customWeight" class="inline-input" placeholder="0"> 
                                <span class="radio-label">กิโลกรัม</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-primary" onclick="submitForm()">บันทึกข้อมูล</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.html'">ยกเลิก</button>
                </div>
            </form>

        </div>
    </div>

    <script>
        // Store form data
        let formData = {};

        // ========== STEP NAVIGATION ==========
        function goToStep(stepNumber) {
            document.querySelectorAll('.step-form').forEach(form => {
                form.classList.remove('active');
            });
            document.getElementById('step' + stepNumber).classList.add('active');
        }

        // ========== VALIDATE STEP 1 ==========
        function validateStep1() {
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password1').value;
            const confirmPassword = document.getElementById('confirmPassword1').value;

            if (!firstName || !lastName || !email || !password || !confirmPassword) {
                showMessage('กรุณากรอกข้อมูลให้ครบถ้วน', 'error');
                return;
            }

            if (!validateEmail(email)) {
                showMessage('รูปแบบอีเมลไม่ถูกต้อง', 'error');
                return;
            }

            if (password.length < 6) {
                showMessage('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร', 'error');
                return;
            }

            if (password !== confirmPassword) {
                showMessage('รหัสผ่านไม่ตรงกัน', 'error');
                return;
            }

            // Store data
            formData.firstName = firstName;
            formData.lastName = lastName;
            formData.email = email;
            formData.password = password;

            goToStep(2);
        }

        // ========== PASSWORD TOGGLE ==========
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const btn = input.parentElement.querySelector('.toggle-password');
            const icon = btn.querySelector('.eye-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-icon', 'mdi:eye-outline');
            } else {
                input.type = 'password';
                icon.setAttribute('data-icon', 'mdi:eye-off-outline');
            }
        }

        // ========== BUSINESS TYPE CHECKBOXES ==========
        let selectedBusinessType = ''; // comma-separated

        function onBizTypeChange() {
            const checked = Array.from(document.querySelectorAll('input[name="biz_type_cb"]:checked')).map(cb => cb.value);
            selectedBusinessType = checked.join(',');
            // style pills
            document.querySelectorAll('input[name="biz_type_cb"]').forEach(cb => {
                const pill = cb.closest('label');
                pill.style.borderColor = cb.checked ? '#123451' : '#e2e8f0';
                pill.style.background  = cb.checked ? '#eef2f7' : '';
                pill.style.fontWeight  = cb.checked ? '600' : '';
            });
            renderDocumentSection(checked);
        }

        // keep for animal type dropdown
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            document.querySelectorAll('.custom-select').forEach(s => {
                if (s.id !== id) s.classList.remove('open');
            });
            el.classList.toggle('open');
        }

        // ========== ANIMAL TYPE ==========
        let selectedAnimalType = '';
        function selectAnimalType(value) {
            const el = document.getElementById('animalTypeSelect');
            const placeholder = el.querySelector('.select-placeholder');
            placeholder.textContent = value === 'exotic pets บางประเภท' ? 'exotic pets บางประเภท (ระบุเพิ่มเติม)' : value;
            placeholder.classList.add('selected');
            el.classList.remove('open');
            selectedAnimalType = value;
            document.getElementById('exoticCustomGroup').style.display =
                value === 'exotic pets บางประเภท' ? 'block' : 'none';
        }

        // ========== DOCUMENT UPLOAD SECTION ==========
        const docDefinitions = {
            'โรงแรม': [
                'หนังสือรับรองบริษัท / ทะเบียนพาณิชย์',
                'ใบอนุญาตโรงแรม',
                'ใบอนุญาตประกอบกิจการที่พัก',
            ],
            'คาเฟ่': [
                'หนังสือรับรองบริษัท / ทะเบียนพาณิชย์',
                'ใบอนุญาตจำหน่ายอาหาร',
            ],
            'ร้านอาหาร': [
                'หนังสือรับรองบริษัท / ทะเบียนพาณิชย์',
                'ใบอนุญาตร้านอาหาร',
                'เอกสารสุขาภิบาล (ถ้ามี)',
            ],
            'อาบน้ำ ตัดขน': [
                'หนังสือรับรองบริษัท / ทะเบียนพาณิชย์',
                'ใบจดทะเบียนธุรกิจ',
                'ใบรับรองการอบรม Grooming (ถ้ามี)',
            ],
            'โรงพยาบาลสัตว์': [
                'หนังสือรับรองบริษัท / ทะเบียนพาณิชย์',
                'ใบอนุญาตสถานพยาบาลสัตว์',
                'ใบประกอบวิชาชีพสัตวแพทย์',
                'รายชื่อสัตวแพทย์',
            ],
        };

        let uploadedDocs = {};

        function renderDocumentSection(types) {
            const section = document.getElementById('documentSection');
            const fieldsContainer = document.getElementById('documentFields');
            const typeArr = Array.isArray(types) ? types : (types ? [types] : []);
            const seen = new Set();
            const docs = [];
            typeArr.forEach(t => {
                (docDefinitions[t] || []).forEach(d => {
                    if (!seen.has(d)) { seen.add(d); docs.push(d); }
                });
            });
            if (!docs.length) { section.style.display = 'none'; return; }

            section.style.display = 'block';
            fieldsContainer.innerHTML = '';
            uploadedDocs = {};

            docs.forEach((docName, idx) => {
                const key = 'doc_' + idx;
                uploadedDocs[key] = [];
                const div = document.createElement('div');
                div.className = 'form-group';
                div.style.cssText = 'border:1px solid #e5e7eb; border-radius:10px; padding:14px; margin-bottom:12px; background:#f9fafb;';
                div.innerHTML = `
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:8px;">
                        ${docName}
                    </label>
                    <button type="button" onclick="document.getElementById('file_${key}').click()"
                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1.5px dashed #9ca3af;border-radius:8px;background:#fff;color:#6b7280;font-size:13px;cursor:pointer;font-family:inherit;">
                        <span style="font-size:16px">+</span> เพิ่มรูปภาพ / ไฟล์
                    </button>
                    <input type="file" id="file_${key}" accept="image/*,.pdf" multiple style="display:none"
                        onchange="handleDocUpload(event, '${key}', '${docName.replace(/'/g,"\\'")}')">
                    <div id="preview_${key}" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px;"></div>
                `;
                fieldsContainer.appendChild(div);
            });
        }

        function handleDocUpload(event, key, label) {
            const files = Array.from(event.target.files);
            uploadedDocs[key] = (uploadedDocs[key] || []).concat(files);
            renderDocPreview(key);
        }

        function renderDocPreview(key) {
            const preview = document.getElementById('preview_' + key);
            preview.innerHTML = '';
            (uploadedDocs[key] || []).forEach((file, i) => {
                const chip = document.createElement('div');
                chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:5px 10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:20px;font-size:12px;color:#1e40af;';
                chip.innerHTML = `
                    <span>${file.type.includes('pdf') ? '📄' : '🖼️'}</span>
                    <span>${file.name}</span>
                    <button type="button" onclick="removeDoc('${key}', ${i})"
                        style="background:none;border:none;color:#6b7280;cursor:pointer;font-size:14px;line-height:1;padding:0 2px;">×</button>
                `;
                preview.appendChild(chip);
            });
        }

        function removeDoc(key, idx) {
            uploadedDocs[key].splice(idx, 1);
            renderDocPreview(key);
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select')) {
                document.querySelectorAll('.custom-select').forEach(s => s.classList.remove('open'));
            }
        });

        // ========== VALIDATE STEP 2 ==========
        function validateStep2() {
            const businessName = document.getElementById('businessName').value.trim();
            if (!businessName) {
                showMessage('กรุณากรอกชื่อสถานประกอบการ', 'error');
                return;
            }
            if (!selectedBusinessType) {
                showMessage('กรุณาเลือกประเภทธุรกิจอย่างน้อย 1 ประเภท', 'error');
                document.getElementById('err_bizType').style.display = 'block';
                return;
            }
            document.getElementById('err_bizType').style.display = 'none';
            if (!selectedAnimalType) {
                showMessage('กรุณาเลือกประเภทสัตว์ที่รับ', 'error');
                return;
            }
            if (selectedAnimalType === 'exotic pets บางประเภท') {
                const custom = document.getElementById('exoticCustomInput').value.trim();
                if (!custom) {
                    showMessage('กรุณาระบุประเภท exotic pets ที่รับ', 'error');
                    return;
                }
            }
            goToStep(3);
        }

        async function submitForm() {
            const businessName = document.getElementById('businessName').value.trim();
            const businessDetails = document.getElementById('businessDetails').value.trim();
            const address = document.getElementById('address').value.trim();
            const province = document.getElementById('province').value.trim();
            
            if (!businessName || !selectedBusinessType || !address || !province) {
                showMessage('กรุณากรอกข้อมูลให้ครบถ้วน', 'error');
                return;
            }

            if (!selectedAnimalType) {
                showMessage('กรุณาเลือกประเภทสัตว์ที่รับ', 'error');
                return;
            }

            let animalTypeFinal = selectedAnimalType;
            if (selectedAnimalType === 'exotic pets บางประเภท') {
                const custom = document.getElementById('exoticCustomInput').value.trim();
                if (custom) animalTypeFinal = 'exotic pets บางประเภท: ' + custom;
            }

            // Get pet conditions
            const petAllowed = document.querySelector('input[name="acceptPets"]:checked').value;
            const petSizeRadio = document.querySelector('input[name="petSize"]:checked');
            let petSizeAllowed = petSizeRadio ? petSizeRadio.value : '';
            let petWeightAllowed = null;

            if (petSizeAllowed === 'custom') {
                petWeightAllowed = parseInt(document.getElementById('customWeight').value) || null;
            } else if (petSizeAllowed === 'small') {
                petWeightAllowed = 10;
            } else if (petSizeAllowed === 'medium') {
                petWeightAllowed = 25;
            }

            // Build FormData (multipart for file uploads)
            const fd = new FormData();
            fd.append('firstName', formData.firstName || '');
            fd.append('lastName', formData.lastName || '');
            fd.append('email', formData.email || '');
            fd.append('password', formData.password || '');
            fd.append('businessName', businessName);
            fd.append('businessType', selectedBusinessType);
            fd.append('animalType', animalTypeFinal);
            fd.append('businessDetails', businessDetails);
            fd.append('address', address);
            fd.append('province', province);
            fd.append('petAllowed', petAllowed);
            fd.append('petSizeAllowed', petSizeAllowed);
            fd.append('petWeightAllowed', petWeightAllowed ?? '');

            // Attach document files
            Object.entries(uploadedDocs).forEach(([key, files]) => {
                files.forEach(file => fd.append('docs[]', file));
            });

            try {
                const response = await fetch('business-register-handler.php', {
                    method: 'POST',
                    body: fd
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'entre_pending.php';
                    }, 1500);
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        }

        // ========== HELPER FUNCTIONS ==========
        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function showMessage(message, type) {
            const box = document.getElementById('messageBox');
            box.textContent = message;
            box.style.display = 'block';
            box.style.backgroundColor = type === 'success' ? '#d4edda' : '#f8d7da';
            box.style.color = type === 'success' ? '#155724' : '#721c24';
            box.style.border = `1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}`;

            setTimeout(() => {
                box.style.display = 'none';
            }, 5000);
        }
    </script>

</body>
</html>