"""add outfit as a try-on source

Revision ID: 2c4f0f7a9d21
Revises: 1a5025488908
Create Date: 2026-11-23 08:00:00
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


revision: str = '2c4f0f7a9d21'
down_revision: Union[str, Sequence[str], None] = '1a5025488908'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


SOURCE_CHECK = (
    '(CASE WHEN wardrobe_item_id IS NULL THEN 0 ELSE 1 END + '
    'CASE WHEN catalog_item_id IS NULL THEN 0 ELSE 1 END + '
    'CASE WHEN outfit_id IS NULL THEN 0 ELSE 1 END) = 1'
)


def upgrade() -> None:
    op.drop_constraint('exactly_one_source', 'try_on_results', type_='check')
    op.add_column('try_on_results', sa.Column('outfit_id', sa.Integer(), nullable=True))
    op.create_check_constraint('exactly_one_source', 'try_on_results', SOURCE_CHECK)


def downgrade() -> None:
    op.drop_constraint('exactly_one_source', 'try_on_results', type_='check')
    op.drop_column('try_on_results', 'outfit_id')
    op.create_check_constraint(
        'exactly_one_source',
        'try_on_results',
        '(wardrobe_item_id IS NULL) <> (catalog_item_id IS NULL)',
    )
