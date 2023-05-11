import {createQuery, SUM, COUNT, DATE, FIELD, queryGetMany, queryFirstMany} from "./QueryBuilder";
import {withRelationBelongsTo, withRelationHasMany} from "./relations";

async function main() {
    // Lấy danh sách data
    const data = await createQuery('connection_units')
        .whereEquals('status', 1)
        .get();

    // Query = alias, group by
    await createQuery('connection_units')
        .select([
            'id',
            DATE('create_date').as('DateAlias'),
            COUNT('status').as('countStatus')
        ])
        .where('date', '>=', '2022-01-01')
        .where('date', '<=', '2022-12-31')
        .groupBy('id')
        .first();

    // Lấy bản ghi đầu tiên
    const entry = await createQuery('connection_units')
        .select(['id'])
        .where('date', '>=', '2022-01-01')
        .where('date', '<=', '2022-12-31')
        .first();


    // Dùng hàm SUM,
    const res3 = await createQuery('connection_units')
        .select([SUM('id').as('sumId')])
        .where('date', '>=', '2022-01-01')
        .where('date', '<=', '2022-12-31')
        .first();


    // Phân trang
    const page = 1;
    const paginateRes = await createQuery('connection_units')
        .select([SUM('id').as('sumId')])
        .where('date', '>=', '2022-01-01')
        .where('date', '<=', '2022-12-31')
        .paginate(page)
        .get();

    // Sắp xếp
    const orderByRes = await createQuery('connection_units')
        .select([SUM('id').as('sumId')])
        .where('date', '>=', '2022-01-01')
        .where('date', '<=', '2022-12-31')
        .orderBy('id', 'asc')
        .paginate(page)
        .get();

    // Relation giữa các bảng
    const entries = await createQuery('report_transaction_types')
        .select(['id', 'transaction_type_id', 'total_count', 'total_amount'])
        .whereEquals('month', 9)
        .whereEquals('year', 2022)
        .get();


    // withRelationBelongsTo
    await withRelationBelongsTo(entries, {
        select: ['id', 'name'],
        table: 'transaction_types',
        alias: 'TransactionType',
        foreignKey: 'transaction_type_id'
    });


    // withRelationHasMany
    const transactionTypes = await createQuery('transaction_types')
        .get();


    await withRelationHasMany(transactionTypes, {
        table: 'transaction_type_codes',
        alias: 'TransactionTypeCodes',
        foreignKey: 'transaction_type_id',
        localKey: 'id',
    });

    /// Lấy nhiều get() cùng lúc để tối ưu request
    const [a, b] = await queryGetMany([
        createQuery('transaction_types').limit(5), createQuery('connection_units').limit(5)
    ]);

    /// Lấy nhiều  first() cùng lúc để tối ưu request
    const [c, d] = await queryFirstMany([
        createQuery('transaction_types').limit(1), createQuery('connection_units').limit(1)
    ]);

}
