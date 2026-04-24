# Race Condition khi Join Event & Cách Fix bằng `lockForUpdate()`

## 1. Vấn đề

Khi một Event có `limit` (giới hạn số ngườitham gia), nếu **2 user khác nhau** click **Join** đúng cùng một thờ điểm, hệ thống cũ có thể **vượt quá giới hạn**.

### Ví dụ

- Event có `limit = 10`, hiện tại đã có **9 ngườ** joined.
- User A và User B gửi request `POST /join` **cùng lúc**.

#### Timeline KHÔNG có lock (race condition)

```
T0  Request A: SELECT COUNT(*) ... → 9  (còn 1 slot)
T0  Request B: SELECT COUNT(*) ... → 9  (cũng đọc được 9!)

T1  Request A: 9 < 10 ? Đúng → INSERT thành công
T2  Request B: 9 < 10 ? Đúng → INSERT thành công

→ Kết quả: Event có 11 ngườ** > limit 10
```

Nguyên nhân: `SELECT COUNT` và `INSERT` là **2 bước riêng biệt**. Giữa lúc đếm và lúc insert, dữ liệu có thể thay đổi bởi request khác.

---

## 2. Cách Fix: `DB::transaction` + `lockForUpdate()`

Sử dụng `SELECT ... FOR UPDATE` của MySQL InnoDB để **khóa row** trong quá trình đọm.

### Code đã sửa (`app/Services/EventUserService.php`)

```php
return DB::transaction(function () use ($user, $event) {
    // 1. Khóa record của user này (nếu đã tồn tại)
    $existing = EventUser::query()
        ->where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->first();

    $alreadyJoined = $existing && $existing->status === EventUser::STATUS_JOINED;

    // 2. Nếu chưa join, đếm số ngườ** joined VÀ khóa các row đó
    if ($event->limit !== null && ! $alreadyJoined) {
        $joinedCount = EventUser::query()
            ->where('event_id', $event->id)
            ->where('status', EventUser::STATUS_JOINED)
            ->lockForUpdate()
            ->count();

        if ($joinedCount >= $event->limit) {
            throw ValidationException::withMessages([
                'limit' => 'Event has reached the participant limit.',
            ]);
        }
    }

    // ... insert/update
});
```

---

## 3. Cơ chế hoạt động

### `lockForUpdate()` là gì?

Đây là lệnh SQL `SELECT ... FOR UPDATE`. Khi chạy, MySQL sẽ **gắn exclusive lock** lên các row khớp điều kiện.

### Timeline CÓ lock (an toàn)

```
T0  Request A: START TRANSACTION
T1  Request A: SELECT ... FOR UPDATE → MySQL GRANT lock cho A
    → A đọc được count = 9

T0  Request B: START TRANSACTION
T1  Request B: SELECT ... FOR UPDATE → MySQL đẩy B vào **lock wait queue**
    → B bị **BLOCK** (đứng yên, không đọc được gì)

T2  Request A: INSERT thành công
T3  Request A: COMMIT → Release lock

T4  Request B: Wake up! Nhận được lock
    → B đọc lại count = **10**
T5  Request B: 10 < 10 ? Sai → Throw ValidationException
T6  Request B: ROLLBACK
```

### Tại sao B phải đợi?

MySQL InnoDB có **Lock Manager** riêng. Khi một connection giữ `FOR UPDATE` lock, connection khác muốn đọc/ghi cùng row phải xếp hàng. Đây là **hardware-level serialization** — không phụ thuộc vào thứ tự "trước/sau" ở mức ứng dụng.

---

## 4. Nếu 2 request đến đúng cùng một thờ điểm?

Dù 2 request đến **đúng cùng một nanosecond**, CPU chỉ thực thi **1 instruction tại 1 thờ điểm**. MySQL Lock Manager sẽ:

1. Nhận cả 2 request gần như đồng thờ
2. Chọn 1 request để **grant lock** (dựa trên kernel scheduling)
3. Request còn lại bị đẩy vào **lock wait queue** và sleep
4. Khi request 1 commit, request 2 wake up và đọc dữ liệu **mới nhất**

**Kết quả:** Luôn chỉ có **1 request** được phép "đếm + quyết định" tại một thờ điểm. Không có cách nào để 2 request cùng đọc được giá trị cũ rồi cùng insert.

---

## 5. Lưu ý

| Vấn đề | Giải thích |
|--------|-----------|
| **Giảm concurrency** | Request B phải đợ A xong. Nếu A chậm, B có thể bị `Lock wait timeout exceeded` (mặc định 50s). |
| **Chỉ lock trong transaction** | Nếu quên `DB::transaction()`, `lockForUpdate()` không có tác dụng vì lock tự động release sau query. |
| **Chỉ lock các row liên quan** | Vì `event_id` có index (foreign key), MySQL chỉ khóa row của event đó, không ảnh hưởng event khác. |

---

## 6. Tóm tắt

- **Race condition** xảy ra khi "đọm" và "ghi" tách rờ nhau, không được bảo vệ.
- **`lockForUpdate()`** biến phép đếm `count()` từ **"đọc snapshot cũ"** thành **"đọc dữ liệu mới nhất và không cho ai đụng vào"**.
- MySQL đảm bảo chỉ có **1 connection** giữ lock tại một thờ điểm — dù request đến cùng lúc.
