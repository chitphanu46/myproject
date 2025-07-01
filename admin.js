$(document).ready(function () {
    $("#technicianCard, #viewTechnicians").click(function () {
        $(".dashboard, #repairRequests, #userList").hide();
        $("#technicianList").show();

        $.get("get_technicians.php", function (data) {
            $("#technicianCard h3").text(data.total_technicians);

            let html = "";
            data.technicians.forEach(t => {
                const img = t.profile_image 
                    ? `http://localhost/code/${t.profile_image}` 
                    : 'http://localhost/code/uploads/default-profile.png';

                html += `
                    <tr>
                        <td>${t.id}</td>
                        <td>${t.name}</td>
                        <td>${t.email}</td>
                        <td>${t.technician_type}</td>
                        <td>${t.specialization}</td>
                        <td>${t.address}</td>
                        <td>${t.phone_number}</td>
                        <td><img src="${img}" width="50" /></td>
                    </tr>
                `;
            });

            $("#technicianTable tbody").html(html);
        });
    });

    // แสดงรายการแจ้งซ่อม
    $("#taskCard, #viewRepairRequests").click(function () {
    $(".dashboard, #technicianList, #userList").hide();
    $("#repairRequests").show();

    $.get("get_repairs.php", function (data) {
        // ✅ อัปเดตจำนวนการแจ้งซ่อม
        $("#taskCard h3").text(data.total_repairs);

        let html = "";
        data.repairs.forEach(r => {
            const img = r.repair_image ? `<img src="http://localhost/code/uploads/${r.repair_image}" width="50" />` : '-';
            html += `<tr>
                <td>${r.id}</td>
                <td>${r.user_id}</td>
                <td>${r.technician_name || '-'}</td>
                <td>${r.problem_description}</td>
                <td>${r.status}</td>
                <td>${r.created_at}</td>
                <td>${r.user_name || '-'}</td>
                <td>${img}</td>
            </tr>`;
        });

        $("#repairRequestsBody").html(html);
    });
});


    // แสดงรายชื่อผู้ใช้
    $("#userCard, #viewUsers").click(function () {
        $(".dashboard, #repairRequests, #technicianList").hide();
        $("#userList").show();

        $.get("get_users.php", function (data) {
            let html = "";
            data.forEach(u => {
                html += `<tr>
                    <td>${u.id}</td>
                    <td>${u.full_name}</td>
                    <td>${u.email}</td>
                    <td>${u.password}</td> 
                    <td>${u.phone}</td>
                    <td>${u.address}</td>
                </tr>`;
            });
            $("#userTable tbody").html(html);
        });
    });
});
