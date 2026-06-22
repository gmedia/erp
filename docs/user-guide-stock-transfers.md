# User Guide: Stock Transfers

## Gambaran Umum

M S T          RP.            ,   ,    . M           .

S      y  ,  ,   ,    y      -. S       ,      .

## M & N

| M | P |  |
|------|------|-----------|
| S T | `/-` | H     |
| T T | `/-` ( ) |     |
|  T | `/-` ( V) |     |
|  T | `/-` ( ) |    |
|   | `/-` ( ) | U     |

## . M H S T

U   S T:

. L   RP   y  z `_`.
.     .
. K  **S T**.
. H           .

[S: H           ]

P      :
- **S **: K    "S  ..."        y.
- ****:    ,  ,  .
- **T**: M     T N,  W, T W, T ,  ,  S.
- **T **: U   .
- **T **: U     .

## . M T S 

U    :

. K  ** N S T**     .
.      .

[S:      ]

. I - :
   - **T N**: N   (,        `ST-XXXX`).
   - **S**: P    (, P , , I T, R, ).
   - ** W**: P     .
   - **T W**: P    .
   - **T **: P   .
   - **  **: P        ().
   - **R y**: P y y   ().

[S:     - ]

. T    **N**  .
. K  ** I**    y  .

[S:       I]

. P  , :
   - **P**: P  y  .
   - **U**: P  .
   - **Qy**: M  y  .
   - **Qy R**: J y  ( ,   ).
   - **U **: y   ().
   - **N**:      ().

[S:    ]

. K **S**     .
. U  -      .
. K **S**     y .

T        . J T N ,        `ST-XXXX`.

## . M  T S

U    :

. P   ,   y  .
. K  **V** ( )       .
.     .

[S:       ]

  :
- I : T N, S,  W, T W, T ,   .
- I : R y, N.
- T :   y   , ,  .
- I :  y,  y, S y, R y   -.

## . M T S

T        (y    P ). U :

. P   ,   y  .
. K  **** ( )   .
.         y  .

[S:     ]

. U - y   .
. U  ,          .
. T     ** I**  .
. K **S**  y .

P         .

## . M T S

T      -. U :

. P   ,   y  .
. K  **** (  )   .
.      : "T    . T     [T N]."

[S:    ]

. K ****   .

T   y  ****.         ,      .

## .  S T

T     y     y:

| S |  |
|--------|-----------|
|  | T  ,   . |
| P  | T   . |
|  | T  ,   . |
| I T |        . |
| R |      . |
|  | T ,     . |

[S:        ]

  y :
. ****  T   .
. **P **  T    ( ).
. ****  T    .
. **I T**      .
. **R**        .

T     , y     .

## . M  M T

### P

G         :

. K        "S  ...".
. S     -      y.

[S: S     y ]

### 

U     :

. G     .
.  y :
   - ** W**:    .
   - **T W**:    .
   - **S**:    .

[S:       ]

. P   y .
. T    y    y .
. U  ,   X      "".

## . M 

K-       :

| K | K |
|-------|------------|
| T N | U    (-Z  Z-). |
|  W | U    . |
| T W | U    . |
| T  | U    (  ). |
|   | U    . |
| S | U   . |

[S: H  T N    ]

K     (-Z, -),     (Z-, -).

## . M   

U      :

. K  ****    .
. S          y .
.        .

[S: T    ]

   - :
- T N
-  W
- T W
- T 
-   
- S
- N
- I  (P, U, Qy, Qy R, U )

## . Iz 

M S T  z  .   z y :

| Iz |  |
|------|-----------|
| `_` |      . |
| `_.` | M   . |
| `_.` | M   . |
| `_.` | M  . |

[S: H    z _]

H       -    .

## FAQ

**Q: Bagaimana cara membuat stock transfer baru?**
A: Buka halaman Stock Transfers, klik tombol Add, lalu isi gudang asal (from warehouse), gudang tujuan (to warehouse), tanggal transfer, dan daftar produk beserta kuantitasnya. Setelah lengkap, simpan untuk membuat transfer dengan status awal.

**Q: Apa perbedaan setiap status pada stock transfer?**
A: Status menggambarkan tahap proses transfer, mulai dari draft/pending saat baru dibuat, in transit ketika barang sedang dikirim, completed saat barang diterima penuh di gudang tujuan, hingga cancelled bila transfer dibatalkan.

**Q: Apa itu qty received dan qty rejected?**
A: Qty received adalah jumlah barang yang benar-benar diterima di gudang tujuan, sedangkan qty rejected adalah jumlah barang yang ditolak karena rusak atau tidak sesuai. Selisih keduanya menentukan stok yang benar-benar masuk.

**Q: Bagaimana cara membatalkan stock transfer?**
A: Pilih transfer yang ingin dibatalkan lalu gunakan aksi cancel. Pembatalan akan mengubah status menjadi cancelled dan tidak memengaruhi stok jika transfer belum completed.

**Q: Apa format penomoran transfer number?**
A: Transfer number dibuat otomatis oleh sistem dengan format `ST-XXXX` (prefix ST diikuti nomor urut), sehingga setiap transfer memiliki nomor unik dan mudah dilacak.

**Q: Apa perbedaan from warehouse dan to warehouse?**
A: From warehouse adalah gudang asal tempat barang dikeluarkan, sedangkan to warehouse adalah gudang tujuan tempat barang akan diterima. Keduanya harus berbeda dalam satu transfer.

**Q: Bagaimana cara filter dan search stock transfer?**
A: Gunakan kolom pencarian untuk mencari berdasarkan transfer number, lalu gunakan filter untuk menyaring berdasarkan gudang asal, gudang tujuan, tanggal, atau status sesuai kebutuhan.

**Q: Bagaimana cara export data stock transfer?**
A: Klik tombol Export pada toolbar untuk mengunduh data transfer ke file Excel. Kolom hasil export mencakup seluruh kolom yang tampil pada tabel, termasuk informasi gudang dan status.

**Q: Apa hubungan stock transfer dengan stock movements?**
A: Setiap stock transfer yang diproses akan mencatat pergerakan stok pada kartu stok (stock movements), baik pengurangan di gudang asal maupun penambahan di gudang tujuan, sehingga riwayat stok tetap akurat.

**Q: Apa yang terjadi pada stok saat transfer completed?**
A: Saat transfer berstatus completed, stok akan berkurang di gudang asal sesuai qty yang dikirim dan bertambah di gudang tujuan sesuai qty received, menjaga keseimbangan stok antar gudang.

**Q: Apa tips penting saat melakukan transfer antar gudang?**
A: Pastikan stok di gudang asal mencukupi sebelum membuat transfer, verifikasi qty received saat barang tiba, dan segera proses pembatalan bila terjadi kesalahan agar data stok tetap konsisten.
