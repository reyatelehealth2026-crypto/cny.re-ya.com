/**
 * Health Profile Component
 * Manages user health profile including personal info, medical history, allergies, and medications
 * 
 * Requirements: 18.1, 18.2, 18.3, 18.4, 18.5, 18.6, 18.7, 18.8, 18.9, 18.10, 18.11, 18.12
 */

class HealthProfileComponent {
    constructor() {
        this.profile = null;
        this.isLoading = false;
        this.editMode = null; // 'personal', 'medical', 'allergies', 'medications'
        this.drugSearchTimeout = null;
        this.drugSearchResults = [];
    }

    /**
     * Initialize the component
     */
    async init() {
        await this.loadProfile();
        this.setupEventListeners();
    }

    /**
     * Load health profile from API
     * Requirements: 18.1, 18.10
     */
    async loadProfile() {
        const lineUserId = window.store?.get('profile')?.userId;
        const accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;

        if (!lineUserId) {
            this.renderLoginRequired();
            return;
        }

        this.isLoading = true;
        this.renderLoading();

        try {
            const url = `${window.APP_CONFIG.BASE_URL}/api/health-profile.php?action=get&line_user_id=${lineUserId}&line_account_id=${accountId}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.profile = data.profile;
                this.render();
            } else {
                throw new Error(data.error || 'Failed to load profile');
            }
        } catch (error) {
            console.error('Error loading health profile:', error);
            this.renderError(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    /**
     * Render the health profile page
     * Requirements: 18.1, 18.10, 18.12
     */
    render() {
        const container = document.getElementById('health-profile-container');
        if (!container) return;

        const { personal_info, medical_conditions, allergies, medications, completion_percent, updated_at } = this.profile || {};

        container.innerHTML = `
            <div class="health-profile-page">
                <!-- Header with completion percentage (Requirement 18.10) -->
                <div class="health-profile-header">
                    <div class="completion-card">
                        <div class="completion-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="circle" stroke-dasharray="${completion_percent || 0}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            </svg>
                            <span class="completion-text">${completion_percent || 0}%</span>
                        </div>
                        <div class="completion-info">
                            <h3>ข้อมูลสุขภาพของคุณ</h3>
                            <p class="completion-desc">${this.getCompletionMessage(completion_percent)}</p>
                            ${updated_at ? `<p class="last-updated">อัพเดทล่าสุด: ${this.formatDate(updated_at)}</p>` : ''}
                        </div>
                    </div>
                </div>

                <!-- Personal Info Section (Requirement 18.2) -->
                <div class="health-section" id="personal-section">
                    <div class="section-header" onclick="window.healthProfile.toggleSection('personal')">
                        <div class="section-title">
                            <i class="fas fa-user-circle"></i>
                            <span>ข้อมูลส่วนตัว</span>
                        </div>
                        <div class="section-actions">
                            <button class="btn-edit" onclick="event.stopPropagation(); window.healthProfile.editSection('personal')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <i class="fas fa-chevron-down section-toggle"></i>
                        </div>
                    </div>
                    <div class="section-content">
                        ${this.renderPersonalInfo(personal_info)}
                    </div>
                </div>

                <!-- Medical History Section (Requirement 18.3) -->
                <div class="health-section" id="medical-section">
                    <div class="section-header" onclick="window.healthProfile.toggleSection('medical')">
                        <div class="section-title">
                            <i class="fas fa-notes-medical"></i>
                            <span>ประวัติการแพทย์</span>
                        </div>
                        <div class="section-actions">
                            <button class="btn-edit" onclick="event.stopPropagation(); window.healthProfile.editSection('medical')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <i class="fas fa-chevron-down section-toggle"></i>
                        </div>
                    </div>
                    <div class="section-content">
                        ${this.renderMedicalHistory(medical_conditions)}
                    </div>
                </div>

                <!-- Allergies Section (Requirement 18.4, 18.5, 18.11) -->
                <div class="health-section" id="allergies-section">
                    <div class="section-header" onclick="window.healthProfile.toggleSection('allergies')">
                        <div class="section-title">
                            <i class="fas fa-allergies"></i>
                            <span>การแพ้ยา</span>
                            ${allergies?.length ? `<span class="badge badge-danger">${allergies.length}</span>` : ''}
                        </div>
                        <div class="section-actions">
                            <button class="btn-add" onclick="event.stopPropagation(); window.healthProfile.showAddAllergy()">
                                <i class="fas fa-plus"></i>
                            </button>
                            <i class="fas fa-chevron-down section-toggle"></i>
                        </div>
                    </div>
                    <div class="section-content">
                        ${this.renderAllergies(allergies)}
                    </div>
                </div>

                <!-- Current Medications Section (Requirement 18.6, 18.7) -->
                <div class="health-section" id="medications-section">
                    <div class="section-header" onclick="window.healthProfile.toggleSection('medications')">
                        <div class="section-title">
                            <i class="fas fa-pills"></i>
                            <span>ยาที่ใช้ประจำ</span>
                            ${medications?.length ? `<span class="badge badge-primary">${medications.length}</span>` : ''}
                        </div>
                        <div class="section-actions">
                            <button class="btn-add" onclick="event.stopPropagation(); window.healthProfile.showAddMedication()">
                                <i class="fas fa-plus"></i>
                            </button>
                            <i class="fas fa-chevron-down section-toggle"></i>
                        </div>
                    </div>
                    <div class="section-content">
                        ${this.renderMedications(medications)}
                    </div>
                </div>

                <!-- Privacy Notice (Requirement 18.8) -->
                <div class="privacy-notice">
                    <i class="fas fa-shield-alt"></i>
                    <p>ข้อมูลสุขภาพของคุณถูกเข้ารหัสและเก็บรักษาอย่างปลอดภัย เภสัชกรจะเห็นข้อมูลนี้เมื่อคุณปรึกษา</p>
                </div>
            </div>
        `;

        // Expand all sections by default
        document.querySelectorAll('.health-section').forEach(section => {
            section.classList.add('expanded');
        });
    }

    /**
     * Render personal info section
     * Requirements: 18.2
     */
    renderPersonalInfo(info) {
        if (!info) {
            return `<p class="empty-state">ยังไม่มีข้อมูล <a href="#" onclick="window.healthProfile.editSection('personal')">เพิ่มข้อมูล</a></p>`;
        }

        const { age, gender, weight, height, blood_type } = info;
        const genderText = { male: 'ชาย', female: 'หญิง', other: 'อื่นๆ' }[gender] || '-';
        const bloodTypeText = blood_type && blood_type !== 'unknown' ? blood_type : '-';

        return `
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">อายุ</span>
                    <span class="info-value">${age ? `${age} ปี` : '-'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">เพศ</span>
                    <span class="info-value">${genderText}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">น้ำหนัก</span>
                    <span class="info-value">${weight ? `${weight} กก.` : '-'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">ส่วนสูง</span>
                    <span class="info-value">${height ? `${height} ซม.` : '-'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">กรุ๊ปเลือด</span>
                    <span class="info-value">${bloodTypeText}</span>
                </div>
                ${weight && height ? `
                <div class="info-item">
                    <span class="info-label">BMI</span>
                    <span class="info-value">${this.calculateBMI(weight, height)}</span>
                </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * Render medical history section
     * Requirements: 18.3
     */
    renderMedicalHistory(conditions) {
        const allConditions = [
            { key: 'diabetes', label: 'เบาหวาน', icon: 'fa-tint' },
            { key: 'hypertension', label: 'ความดันโลหิตสูง', icon: 'fa-heartbeat' },
            { key: 'heart_disease', label: 'โรคหัวใจ', icon: 'fa-heart' },
            { key: 'kidney_disease', label: 'โรคไต', icon: 'fa-kidneys' },
            { key: 'liver_disease', label: 'โรคตับ', icon: 'fa-liver' },
            { key: 'pregnancy', label: 'ตั้งครรภ์', icon: 'fa-baby' },
            { key: 'asthma', label: 'หอบหืด', icon: 'fa-lungs' },
            { key: 'thyroid', label: 'โรคไทรอยด์', icon: 'fa-disease' },
            { key: 'cancer', label: 'มะเร็ง', icon: 'fa-ribbon' },
            { key: 'other', label: 'อื่นๆ', icon: 'fa-plus-circle' }
        ];

        const activeConditions = conditions || [];

        if (activeConditions.length === 0) {
            return `<p class="empty-state">ไม่มีโรคประจำตัว <a href="#" onclick="window.healthProfile.editSection('medical')">แก้ไข</a></p>`;
        }

        return `
            <div class="conditions-list">
                ${activeConditions.map(c => {
                    const condition = allConditions.find(ac => ac.key === c) || { label: c, icon: 'fa-circle' };
                    return `
                        <div class="condition-tag">
                            <i class="fas ${condition.icon}"></i>
                            <span>${condition.label}</span>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    /**
     * Render allergies section
     * Requirements: 18.4, 18.5
     */
    renderAllergies(allergies) {
        if (!allergies || allergies.length === 0) {
            return `<p class="empty-state">ไม่มีประวัติแพ้ยา <a href="#" onclick="window.healthProfile.showAddAllergy()">เพิ่มข้อมูล</a></p>`;
        }

        const reactionLabels = {
            rash: 'ผื่นคัน',
            breathing: 'หายใจลำบาก',
            swelling: 'บวม',
            other: 'อื่นๆ'
        };

        const severityColors = {
            mild: 'warning',
            moderate: 'orange',
            severe: 'danger'
        };

        return `
            <div class="allergies-list">
                ${allergies.map(a => `
                    <div class="allergy-item severity-${a.severity || 'moderate'}">
                        <div class="allergy-info">
                            <div class="allergy-drug">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span class="drug-name">${a.drug_name}</span>
                            </div>
                            <div class="allergy-details">
                                <span class="reaction-type">${reactionLabels[a.reaction_type] || a.reaction_type}</span>
                                <span class="severity-badge ${severityColors[a.severity] || 'warning'}">${this.getSeverityLabel(a.severity)}</span>
                            </div>
                            ${a.reaction_notes ? `<p class="allergy-notes">${a.reaction_notes}</p>` : ''}
                        </div>
                        <button class="btn-remove" onclick="window.healthProfile.removeAllergy(${a.id})" title="ลบ">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Render medications section
     * Requirements: 18.6
     */
    renderMedications(medications) {
        if (!medications || medications.length === 0) {
            return `<p class="empty-state">ไม่มียาที่ใช้ประจำ <a href="#" onclick="window.healthProfile.showAddMedication()">เพิ่มข้อมูล</a></p>`;
        }

        return `
            <div class="medications-list">
                ${medications.map(m => `
                    <div class="medication-item">
                        <div class="medication-icon">
                            <i class="fas fa-capsules"></i>
                        </div>
                        <div class="medication-info">
                            <h4 class="medication-name">${m.medication_name}</h4>
                            <div class="medication-details">
                                ${m.dosage ? `<span class="dosage"><i class="fas fa-prescription"></i> ${m.dosage}</span>` : ''}
                                ${m.frequency ? `<span class="frequency"><i class="fas fa-clock"></i> ${m.frequency}</span>` : ''}
                            </div>
                            ${m.notes ? `<p class="medication-notes">${m.notes}</p>` : ''}
                        </div>
                        <div class="medication-actions">
                            <button class="btn-edit-sm" onclick="window.healthProfile.editMedication(${m.id})" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-remove" onclick="window.healthProfile.removeMedication(${m.id})" title="ลบ">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Toggle section expand/collapse
     */
    toggleSection(sectionId) {
        const section = document.getElementById(`${sectionId}-section`);
        if (section) {
            section.classList.toggle('expanded');
        }
    }

    /**
     * Edit section - show modal
     */
    editSection(sectionType) {
        this.editMode = sectionType;
        
        switch (sectionType) {
            case 'personal':
                this.showPersonalInfoModal();
                break;
            case 'medical':
                this.showMedicalHistoryModal();
                break;
        }
    }

    /**
     * Show personal info edit modal
     * Requirements: 18.2
     */
    showPersonalInfoModal() {
        const info = this.profile?.personal_info || {};
        
        const modalHtml = `
            <div class="modal-overlay" onclick="window.healthProfile.closeModal()">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h3><i class="fas fa-user-circle"></i> แก้ไขข้อมูลส่วนตัว</h3>
                        <button class="modal-close" onclick="window.healthProfile.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="personal-info-form" onsubmit="window.healthProfile.savePersonalInfo(event)">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>อายุ (ปี)</label>
                                    <input type="number" name="age" value="${info.age || ''}" min="0" max="150" placeholder="เช่น 35">
                                </div>
                                <div class="form-group">
                                    <label>เพศ</label>
                                    <select name="gender">
                                        <option value="">เลือก</option>
                                        <option value="male" ${info.gender === 'male' ? 'selected' : ''}>ชาย</option>
                                        <option value="female" ${info.gender === 'female' ? 'selected' : ''}>หญิง</option>
                                        <option value="other" ${info.gender === 'other' ? 'selected' : ''}>อื่นๆ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>น้ำหนัก (กก.)</label>
                                    <input type="number" name="weight" value="${info.weight || ''}" min="0" max="500" step="0.1" placeholder="เช่น 65.5">
                                </div>
                                <div class="form-group">
                                    <label>ส่วนสูง (ซม.)</label>
                                    <input type="number" name="height" value="${info.height || ''}" min="0" max="300" step="0.1" placeholder="เช่น 170">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>กรุ๊ปเลือด</label>
                                <select name="blood_type">
                                    <option value="unknown" ${!info.blood_type || info.blood_type === 'unknown' ? 'selected' : ''}>ไม่ทราบ</option>
                                    <option value="A" ${info.blood_type === 'A' ? 'selected' : ''}>A</option>
                                    <option value="B" ${info.blood_type === 'B' ? 'selected' : ''}>B</option>
                                    <option value="AB" ${info.blood_type === 'AB' ? 'selected' : ''}>AB</option>
                                    <option value="O" ${info.blood_type === 'O' ? 'selected' : ''}>O</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.healthProfile.closeModal()">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        this.showModal(modalHtml);
    }

    /**
     * Show medical history edit modal
     * Requirements: 18.3
     */
    showMedicalHistoryModal() {
        const conditions = this.profile?.medical_conditions || [];
        
        const allConditions = [
            { key: 'diabetes', label: 'เบาหวาน' },
            { key: 'hypertension', label: 'ความดันโลหิตสูง' },
            { key: 'heart_disease', label: 'โรคหัวใจ' },
            { key: 'kidney_disease', label: 'โรคไต' },
            { key: 'liver_disease', label: 'โรคตับ' },
            { key: 'pregnancy', label: 'ตั้งครรภ์' },
            { key: 'asthma', label: 'หอบหืด' },
            { key: 'thyroid', label: 'โรคไทรอยด์' },
            { key: 'cancer', label: 'มะเร็ง' },
            { key: 'other', label: 'อื่นๆ' }
        ];

        const modalHtml = `
            <div class="modal-overlay" onclick="window.healthProfile.closeModal()">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h3><i class="fas fa-notes-medical"></i> แก้ไขประวัติการแพทย์</h3>
                        <button class="modal-close" onclick="window.healthProfile.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="medical-history-form" onsubmit="window.healthProfile.saveMedicalHistory(event)">
                            <p class="form-hint">เลือกโรคประจำตัวที่คุณมี (ถ้ามี)</p>
                            <div class="checkbox-grid">
                                ${allConditions.map(c => `
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="conditions" value="${c.key}" ${conditions.includes(c.key) ? 'checked' : ''}>
                                        <span class="checkbox-label">${c.label}</span>
                                    </label>
                                `).join('')}
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.healthProfile.closeModal()">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        this.showModal(modalHtml);
    }

    /**
     * Show add allergy modal
     * Requirements: 18.4, 18.5
     */
    showAddAllergy() {
        const modalHtml = `
            <div class="modal-overlay" onclick="window.healthProfile.closeModal()">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h3><i class="fas fa-allergies"></i> เพิ่มการแพ้ยา</h3>
                        <button class="modal-close" onclick="window.healthProfile.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="add-allergy-form" onsubmit="window.healthProfile.saveAllergy(event)">
                            <div class="form-group">
                                <label>ชื่อยาที่แพ้ *</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" name="drug_name" id="drug-search-input" 
                                           placeholder="พิมพ์ชื่อยา..." required
                                           oninput="window.healthProfile.searchDrugs(this.value)"
                                           autocomplete="off">
                                    <div id="drug-search-results" class="autocomplete-results hidden"></div>
                                </div>
                                <input type="hidden" name="drug_id" id="drug-id-input">
                            </div>
                            <div class="form-group">
                                <label>อาการที่เกิด *</label>
                                <select name="reaction_type" required>
                                    <option value="">เลือกอาการ</option>
                                    <option value="rash">ผื่นคัน</option>
                                    <option value="breathing">หายใจลำบาก</option>
                                    <option value="swelling">บวม</option>
                                    <option value="other">อื่นๆ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>ความรุนแรง</label>
                                <select name="severity">
                                    <option value="mild">เล็กน้อย</option>
                                    <option value="moderate" selected>ปานกลาง</option>
                                    <option value="severe">รุนแรง</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>รายละเอียดเพิ่มเติม</label>
                                <textarea name="reaction_notes" rows="2" placeholder="อธิบายอาการเพิ่มเติม..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.healthProfile.closeModal()">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary">เพิ่ม</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        this.showModal(modalHtml);
    }

    /**
     * Show add medication modal
     * Requirements: 18.6
     */
    showAddMedication() {
        const modalHtml = `
            <div class="modal-overlay" onclick="window.healthProfile.closeModal()">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h3><i class="fas fa-pills"></i> เพิ่มยาที่ใช้ประจำ</h3>
                        <button class="modal-close" onclick="window.healthProfile.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="add-medication-form" onsubmit="window.healthProfile.saveMedication(event)">
                            <div class="form-group">
                                <label>ชื่อยา *</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" name="medication_name" id="med-search-input" 
                                           placeholder="พิมพ์ชื่อยา..." required
                                           oninput="window.healthProfile.searchMedications(this.value)"
                                           autocomplete="off">
                                    <div id="med-search-results" class="autocomplete-results hidden"></div>
                                </div>
                                <input type="hidden" name="product_id" id="med-product-id-input">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>ขนาดยา</label>
                                    <input type="text" name="dosage" placeholder="เช่น 500mg">
                                </div>
                                <div class="form-group">
                                    <label>ความถี่</label>
                                    <select name="frequency">
                                        <option value="">เลือก</option>
                                        <option value="วันละ 1 ครั้ง">วันละ 1 ครั้ง</option>
                                        <option value="วันละ 2 ครั้ง">วันละ 2 ครั้ง</option>
                                        <option value="วันละ 3 ครั้ง">วันละ 3 ครั้ง</option>
                                        <option value="ก่อนนอน">ก่อนนอน</option>
                                        <option value="เมื่อมีอาการ">เมื่อมีอาการ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>วันที่เริ่มใช้</label>
                                <input type="date" name="start_date">
                            </div>
                            <div class="form-group">
                                <label>หมายเหตุ</label>
                                <textarea name="notes" rows="2" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.healthProfile.closeModal()">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary">เพิ่ม</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        this.showModal(modalHtml);
    }

    /**
     * Search drugs for autocomplete
     * Requirements: 18.4
     */
    async searchDrugs(query) {
        if (this.drugSearchTimeout) {
            clearTimeout(this.drugSearchTimeout);
        }

        if (query.length < 2) {
            this.hideDrugResults();
            return;
        }

        this.drugSearchTimeout = setTimeout(async () => {
            try {
                const url = `${window.APP_CONFIG.BASE_URL}/api/health-profile.php?action=search_drugs&q=${encodeURIComponent(query)}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success && data.drugs) {
                    this.showDrugResults(data.drugs, 'drug');
                }
            } catch (error) {
                console.error('Drug search error:', error);
            }
        }, 300);
    }

    /**
     * Search medications for autocomplete
     */
    async searchMedications(query) {
        if (this.drugSearchTimeout) {
            clearTimeout(this.drugSearchTimeout);
        }

        if (query.length < 2) {
            this.hideMedResults();
            return;
        }

        this.drugSearchTimeout = setTimeout(async () => {
            try {
                const url = `${window.APP_CONFIG.BASE_URL}/api/health-profile.php?action=search_drugs&q=${encodeURIComponent(query)}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success && data.drugs) {
                    this.showDrugResults(data.drugs, 'med');
                }
            } catch (error) {
                console.error('Medication search error:', error);
            }
        }, 300);
    }

    /**
     * Show drug search results
     */
    showDrugResults(drugs, type) {
        const resultsEl = document.getElementById(type === 'drug' ? 'drug-search-results' : 'med-search-results');
        if (!resultsEl) return;

        if (drugs.length === 0) {
            resultsEl.innerHTML = '<div class="autocomplete-item no-results">ไม่พบยาที่ค้นหา</div>';
        } else {
            resultsEl.innerHTML = drugs.map(d => `
                <div class="autocomplete-item" onclick="window.healthProfile.selectDrug(${d.id}, '${d.name.replace(/'/g, "\\'")}', '${type}')">
                    <span class="drug-name">${d.name}</span>
                    ${d.generic_name ? `<span class="generic-name">${d.generic_name}</span>` : ''}
                </div>
            `).join('');
        }

        resultsEl.classList.remove('hidden');
    }

    /**
     * Select drug from autocomplete
     */
    selectDrug(id, name, type) {
        if (type === 'drug') {
            document.getElementById('drug-search-input').value = name;
            document.getElementById('drug-id-input').value = id;
            this.hideDrugResults();
        } else {
            document.getElementById('med-search-input').value = name;
            document.getElementById('med-product-id-input').value = id;
            this.hideMedResults();
        }
    }

    hideDrugResults() {
        const el = document.getElementById('drug-search-results');
        if (el) el.classList.add('hidden');
    }

    hideMedResults() {
        const el = document.getElementById('med-search-results');
        if (el) el.classList.add('hidden');
    }

    /**
     * Save personal info
     * Requirements: 18.2
     */
    async savePersonalInfo(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        const lineUserId = window.store?.get('profile')?.userId;
        const accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;

        if (!lineUserId) {
            window.liffApp?.showToast('กรุณาเข้าสู่ระบบ', 'error');
            return;
        }

        const data = {
            action: 'update_personal',
            line_user_id: lineUserId,
            line_account_id: accountId,
            age: formData.get('age') || null,
            gender: formData.get('gender') || null,
            weight: formData.get('weight') || null,
            height: formData.get('height') || null,
            blood_type: formData.get('blood_type') || 'unknown'
        };

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('บันทึกข้อมูลแล้ว', 'success');
                this.closeModal();
                await this.loadProfile();
                
                // Send LIFF message for health profile update
                if (window.liffMessageBridge) {
                    window.liffMessageBridge.sendActionMessage('health_updated', {});
                }
            } else {
                throw new Error(result.error || 'Failed to save');
            }
        } catch (error) {
            console.error('Save personal info error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
        }
    }

    /**
     * Save medical history
     * Requirements: 18.3
     */
    async saveMedicalHistory(event) {
        event.preventDefault();
        
        const form = event.target;
        const checkboxes = form.querySelectorAll('input[name="conditions"]:checked');
        const conditions = Array.from(checkboxes).map(cb => cb.value);
        
        const lineUserId = window.store?.get('profile')?.userId;
        const accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;

        if (!lineUserId) {
            window.liffApp?.showToast('กรุณาเข้าสู่ระบบ', 'error');
            return;
        }

        const data = {
            action: 'update_medical_history',
            line_user_id: lineUserId,
            line_account_id: accountId,
            conditions: conditions
        };

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('บันทึกประวัติการแพทย์แล้ว', 'success');
                this.closeModal();
                await this.loadProfile();
            } else {
                throw new Error(result.error || 'Failed to save');
            }
        } catch (error) {
            console.error('Save medical history error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
        }
    }

    /**
     * Save allergy
     * Requirements: 18.4, 18.5
     */
    async saveAllergy(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        const lineUserId = window.store?.get('profile')?.userId;
        const accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;

        if (!lineUserId) {
            window.liffApp?.showToast('กรุณาเข้าสู่ระบบ', 'error');
            return;
        }

        const data = {
            action: 'add_allergy',
            line_user_id: lineUserId,
            line_account_id: accountId,
            drug_name: formData.get('drug_name'),
            drug_id: formData.get('drug_id') || null,
            reaction_type: formData.get('reaction_type'),
            severity: formData.get('severity') || 'moderate',
            reaction_notes: formData.get('reaction_notes') || ''
        };

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('เพิ่มข้อมูลการแพ้ยาแล้ว', 'success');
                this.closeModal();
                await this.loadProfile();
            } else {
                throw new Error(result.error || 'Failed to save');
            }
        } catch (error) {
            console.error('Save allergy error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
        }
    }

    /**
     * Remove allergy
     */
    async removeAllergy(allergyId) {
        if (!confirm('ต้องการลบข้อมูลการแพ้ยานี้?')) return;
        
        const lineUserId = window.store?.get('profile')?.userId;

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'remove_allergy',
                    line_user_id: lineUserId,
                    allergy_id: allergyId
                })
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('ลบข้อมูลแล้ว', 'success');
                await this.loadProfile();
            } else {
                throw new Error(result.error || 'Failed to remove');
            }
        } catch (error) {
            console.error('Remove allergy error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด', 'error');
        }
    }

    /**
     * Save medication
     * Requirements: 18.6, 18.7
     */
    async saveMedication(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        const lineUserId = window.store?.get('profile')?.userId;
        const accountId = window.APP_CONFIG?.ACCOUNT_ID || 0;

        if (!lineUserId) {
            window.liffApp?.showToast('กรุณาเข้าสู่ระบบ', 'error');
            return;
        }

        const data = {
            action: 'add_medication',
            line_user_id: lineUserId,
            line_account_id: accountId,
            medication_name: formData.get('medication_name'),
            product_id: formData.get('product_id') || null,
            dosage: formData.get('dosage') || '',
            frequency: formData.get('frequency') || '',
            start_date: formData.get('start_date') || null,
            notes: formData.get('notes') || ''
        };

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('เพิ่มยาที่ใช้ประจำแล้ว', 'success');
                this.closeModal();
                await this.loadProfile();

                // Check for interactions (Requirement 18.7)
                if (result.has_interactions && result.interactions) {
                    this.showInteractionWarning(result.interactions);
                }
            } else {
                throw new Error(result.error || 'Failed to save');
            }
        } catch (error) {
            console.error('Save medication error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด: ' + error.message, 'error');
        }
    }

    /**
     * Show interaction warning modal
     * Requirements: 18.7
     */
    showInteractionWarning(interactions) {
        const severityColors = {
            mild: '#FCD34D',
            moderate: '#F59E0B',
            severe: '#EF4444'
        };

        const modalHtml = `
            <div class="modal-overlay" onclick="window.healthProfile.closeModal()">
                <div class="modal-content interaction-warning-modal" onclick="event.stopPropagation()">
                    <div class="modal-header warning">
                        <h3><i class="fas fa-exclamation-triangle"></i> พบปฏิกิริยาระหว่างยา</h3>
                        <button class="modal-close" onclick="window.healthProfile.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="warning-intro">ยาที่คุณเพิ่มอาจมีปฏิกิริยากับยาที่ใช้อยู่:</p>
                        <div class="interactions-list">
                            ${interactions.map(i => `
                                <div class="interaction-item" style="border-left-color: ${severityColors[i.severity] || severityColors.moderate}">
                                    <div class="interaction-drugs">
                                        <strong>${i.drug1}</strong> + <strong>${i.drug2}</strong>
                                    </div>
                                    <p class="interaction-desc">${i.description}</p>
                                    <p class="interaction-recommendation"><i class="fas fa-info-circle"></i> ${i.recommendation}</p>
                                </div>
                            `).join('')}
                        </div>
                        <div class="form-actions">
                            <button class="btn btn-primary" onclick="window.healthProfile.closeModal()">รับทราบ</button>
                            <button class="btn btn-secondary" onclick="window.router.navigate('/video-call'); window.healthProfile.closeModal();">
                                <i class="fas fa-user-md"></i> ปรึกษาเภสัชกร
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.showModal(modalHtml);
    }

    /**
     * Edit medication
     */
    editMedication(medicationId) {
        const medication = this.profile?.medications?.find(m => m.id == medicationId);
        if (!medication) return;

        const modalHtml = `
            <div class="modal-overlay" onclick="window.healthProfile.closeModal()">
                <div class="modal-content" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h3><i class="fas fa-edit"></i> แก้ไขยา</h3>
                        <button class="modal-close" onclick="window.healthProfile.closeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="edit-medication-form" onsubmit="window.healthProfile.updateMedication(event, ${medicationId})">
                            <div class="form-group">
                                <label>ชื่อยา</label>
                                <input type="text" value="${medication.medication_name}" disabled class="disabled">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>ขนาดยา</label>
                                    <input type="text" name="dosage" value="${medication.dosage || ''}" placeholder="เช่น 500mg">
                                </div>
                                <div class="form-group">
                                    <label>ความถี่</label>
                                    <select name="frequency">
                                        <option value="">เลือก</option>
                                        <option value="วันละ 1 ครั้ง" ${medication.frequency === 'วันละ 1 ครั้ง' ? 'selected' : ''}>วันละ 1 ครั้ง</option>
                                        <option value="วันละ 2 ครั้ง" ${medication.frequency === 'วันละ 2 ครั้ง' ? 'selected' : ''}>วันละ 2 ครั้ง</option>
                                        <option value="วันละ 3 ครั้ง" ${medication.frequency === 'วันละ 3 ครั้ง' ? 'selected' : ''}>วันละ 3 ครั้ง</option>
                                        <option value="ก่อนนอน" ${medication.frequency === 'ก่อนนอน' ? 'selected' : ''}>ก่อนนอน</option>
                                        <option value="เมื่อมีอาการ" ${medication.frequency === 'เมื่อมีอาการ' ? 'selected' : ''}>เมื่อมีอาการ</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>หมายเหตุ</label>
                                <textarea name="notes" rows="2">${medication.notes || ''}</textarea>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-secondary" onclick="window.healthProfile.closeModal()">ยกเลิก</button>
                                <button type="submit" class="btn btn-primary">บันทึก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        this.showModal(modalHtml);
    }

    /**
     * Update medication
     */
    async updateMedication(event, medicationId) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        const lineUserId = window.store?.get('profile')?.userId;

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_medication',
                    line_user_id: lineUserId,
                    medication_id: medicationId,
                    dosage: formData.get('dosage') || '',
                    frequency: formData.get('frequency') || '',
                    notes: formData.get('notes') || ''
                })
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('อัพเดทข้อมูลแล้ว', 'success');
                this.closeModal();
                await this.loadProfile();
            } else {
                throw new Error(result.error || 'Failed to update');
            }
        } catch (error) {
            console.error('Update medication error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด', 'error');
        }
    }

    /**
     * Remove medication
     */
    async removeMedication(medicationId) {
        if (!confirm('ต้องการลบยานี้ออกจากรายการ?')) return;
        
        const lineUserId = window.store?.get('profile')?.userId;

        try {
            const response = await fetch(`${window.APP_CONFIG.BASE_URL}/api/health-profile.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'remove_medication',
                    line_user_id: lineUserId,
                    medication_id: medicationId
                })
            });

            const result = await response.json();

            if (result.success) {
                window.liffApp?.showToast('ลบยาออกจากรายการแล้ว', 'success');
                await this.loadProfile();
            } else {
                throw new Error(result.error || 'Failed to remove');
            }
        } catch (error) {
            console.error('Remove medication error:', error);
            window.liffApp?.showToast('เกิดข้อผิดพลาด', 'error');
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Show modal
     */
    showModal(html) {
        const container = document.getElementById('modal-container');
        if (container) {
            container.innerHTML = html;
            container.classList.remove('hidden');
        }
    }

    /**
     * Close modal
     */
    closeModal() {
        const container = document.getElementById('modal-container');
        if (container) {
            container.classList.add('hidden');
            container.innerHTML = '';
        }
        this.editMode = null;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    /**
     * Render loading state
     */
    renderLoading() {
        const container = document.getElementById('health-profile-container');
        if (!container) return;

        container.innerHTML = `
            <div class="health-profile-loading">
                <div class="skeleton-header">
                    <div class="skeleton skeleton-circle"></div>
                    <div class="skeleton-text-group">
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-text short"></div>
                    </div>
                </div>
                ${[1, 2, 3, 4].map(() => `
                    <div class="skeleton-section">
                        <div class="skeleton skeleton-text"></div>
                        <div class="skeleton skeleton-block"></div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Render login required state
     */
    renderLoginRequired() {
        const container = document.getElementById('health-profile-container');
        if (!container) return;

        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h3>กรุณาเข้าสู่ระบบ</h3>
                <p>เข้าสู่ระบบเพื่อจัดการข้อมูลสุขภาพของคุณ</p>
                <button class="btn btn-primary" onclick="window.liffApp?.login()">
                    <i class="fab fa-line"></i> เข้าสู่ระบบ LINE
                </button>
            </div>
        `;
    }

    /**
     * Render error state
     */
    renderError(message) {
        const container = document.getElementById('health-profile-container');
        if (!container) return;

        container.innerHTML = `
            <div class="empty-state error">
                <div class="empty-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>เกิดข้อผิดพลาด</h3>
                <p>${message || 'ไม่สามารถโหลดข้อมูลได้'}</p>
                <button class="btn btn-primary" onclick="window.healthProfile.loadProfile()">
                    <i class="fas fa-redo"></i> ลองใหม่
                </button>
            </div>
        `;
    }

    /**
     * Get completion message based on percentage
     */
    getCompletionMessage(percent) {
        if (percent >= 100) return 'ข้อมูลครบถ้วน!';
        if (percent >= 75) return 'เกือบครบแล้ว กรอกเพิ่มอีกนิด';
        if (percent >= 50) return 'กรอกข้อมูลเพิ่มเพื่อรับคำแนะนำที่ดีขึ้น';
        if (percent >= 25) return 'เริ่มต้นดี! กรอกข้อมูลเพิ่มเติม';
        return 'เริ่มกรอกข้อมูลสุขภาพของคุณ';
    }

    /**
     * Calculate BMI
     */
    calculateBMI(weight, height) {
        if (!weight || !height) return '-';
        const heightM = height / 100;
        const bmi = weight / (heightM * heightM);
        const bmiValue = bmi.toFixed(1);
        
        let status = '';
        if (bmi < 18.5) status = '(ผอม)';
        else if (bmi < 25) status = '(ปกติ)';
        else if (bmi < 30) status = '(น้ำหนักเกิน)';
        else status = '(อ้วน)';
        
        return `${bmiValue} ${status}`;
    }

    /**
     * Get severity label
     */
    getSeverityLabel(severity) {
        const labels = {
            mild: 'เล็กน้อย',
            moderate: 'ปานกลาง',
            severe: 'รุนแรง'
        };
        return labels[severity] || severity;
    }

    /**
     * Format date
     */
    formatDate(dateStr) {
        if (!dateStr) return '-';
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString('th-TH', { 
                day: 'numeric', 
                month: 'short', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateStr;
        }
    }
}

// Create global instance
window.healthProfile = new HealthProfileComponent();
